#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// --- CONFIGURACIÓN DE PINES ---
// PZEM-004T (UART Modbus RTU)
#define PZEM_RX_PIN 16
#define PZEM_TX_PIN 17

// Pantalla OLED (I2C)
#define SCREEN_WIDTH 128 
#define SCREEN_HEIGHT 64 
#define OLED_RESET -1  
#define SCREEN_ADDRESS 0x3C 

// Relay
#define RELAY_PIN 4 

// --- CONFIGURACIÓN DE RED ---
const char* ssid = "TU_NOMBRE_WIFI";
const char* password = "TU_CONTRASEÑA_WIFI";
const char* webhookUrl = "TU_URL_DEL_WEBHOOK"; // Pega aquí tu URL de Webhook

// --- VARIABLES GLOBALES ---
unsigned long previousMillisWebhook = 0;
const long intervalWebhook = 5000; // 5 segundos para webhook

unsigned long previousMillisPZEM = 0;
const long intervalPZEM = 2000; // 2 segundos para leer sensor

// Variables de mediciones
float last_voltage = 0.0;
float last_current = 0.0;
float last_power = 0.0;
float last_energy = 0.0;
float last_frequency = 0.0;
float last_pf = 0.0;

// Instancia de pantalla
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// Trama fija para leer los 10 registros del PZEM-004T V3.0 usando Modbus RTU
// (0x01: esclavo, 0x04: leer input registers, 0x00 0x00: dir inicio, 0x00 0x0A: leer 10 regs, 0x70 0x0D: CRC16)
const byte pzemReadCmd[] = {0x01, 0x04, 0x00, 0x00, 0x00, 0x0A, 0x70, 0x0D};

// Prototipos
void mostrarMensaje(String linea1, String linea2); 
void actualizarPantalla(float v, float c, float p); 
void enviarDatosWebhook(float v, float c, float p, float e); 

void setup() {
  Serial.begin(115200);
  
  // Inicializar Serial2 para leer PZEM
  Serial2.begin(9600, SERIAL_8N1, PZEM_RX_PIN, PZEM_TX_PIN);

  // Inicializar Relay
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH);

  // Inicializar Pantalla OLED
  if(!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("Fallo OLED - Revisa conexiones I2C"));
    for(;;); 
  }
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.display();
  
  mostrarMensaje("Iniciando Monitor...", "Conectando WiFi...");

  // Conectar a WiFi
  WiFi.begin(ssid, password);
  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }
  
  if(WiFi.status() == WL_CONNECTED){
    Serial.println("\nConectado a WiFi");
    Serial.println(WiFi.localIP());
    mostrarMensaje("WiFi Conectado", WiFi.localIP().toString());
  } else {
    Serial.println("\nFallo WiFi");
    mostrarMensaje("Error WiFi", "Modo Offline");
  }
  delay(2000);
}

void loop() {
  unsigned long currentMillis = millis();

  // 1. LEER DATOS CON MODBUS RTU DIRECTO
  if (currentMillis - previousMillisPZEM >= intervalPZEM) {
    previousMillisPZEM = currentMillis;

    // Limpiar el buffer de entrada antes de preguntar
    while(Serial2.available()) { Serial2.read(); }
    
    // Enviar solicitud de lectura Modbus
    Serial2.write(pzemReadCmd, sizeof(pzemReadCmd));
    
    // Esperar respuesta (esperamos 25 bytes)
    delay(100); 
    
    if (Serial2.available() >= 25) {
      byte response[25];
      Serial2.readBytes(response, 25);
      
      // Validar si el paquete empieza correctamente (0x01 0x04 0x14)
      if (response[0] == 0x01 && response[1] == 0x04 && response[2] == 0x14) {
        
        // 1. Voltaje (Registro 0) - Resolucion 0.1V
        uint16_t v_raw = (response[3] << 8) | response[4];
        last_voltage = v_raw / 10.0;
        
        // 2. Corriente (Registro 1 y 2: Bajo y Alto, 32 bit) - Resolucion 0.001A
        uint32_t c_raw = (response[5] << 8) | response[6] | (response[7] << 24) | (response[8] << 16);
        // Según documentación, el registro 1 es Low, registro 2 es High
        // Si no concuerda, a menudo es simplemente: (High_Reg << 16) | Low_Reg;
        // Los datos vienen: [3,4]=V, [5,6]=C_Low, [7,8]=C_High.
        uint32_t current_raw = (response[7] << 24) | (response[8] << 16) | (response[5] << 8) | response[6];
        last_current = current_raw / 1000.0;
        
        // 3. Potencia (Registro 3 y 4: Bajo y Alto, 32 bit) - Resolucion 0.1W
        uint32_t power_raw = (response[11] << 24) | (response[12] << 16) | (response[9] << 8) | response[10];
        last_power = power_raw / 10.0;
        
        // 4. Energía (Registro 5 y 6: Bajo y Alto, 32 bit) - Resolucion 1Wh
        uint32_t energy_raw = (response[15] << 24) | (response[16] << 16) | (response[13] << 8) | response[14];
        last_energy = energy_raw; // Ya está en Wh
        
        // 5. Frecuencia (Registro 7) - Resolucion 0.1Hz
        uint16_t freq_raw = (response[17] << 8) | response[18];
        last_frequency = freq_raw / 10.0;
        
        // 6. Factor de Potencia (Registro 8) - Resolucion 0.01
        uint16_t pf_raw = (response[19] << 8) | response[20];
        last_pf = pf_raw / 100.0;

        Serial.print("Lectura OK - V: "); Serial.print(last_voltage); 
        Serial.print("V, I: "); Serial.print(last_current, 3); 
        Serial.print("A, P: "); Serial.print(last_power); 
        Serial.print("W, E: "); Serial.print(last_energy); Serial.println("Wh");
        
      } else {
         Serial.println("Cabecera de respuesta Modbus incorrecta.");
      }
    } else {
      Serial.println("Error leyendo sensor PZEM (Tiempo de espera agotado o sin datos).");
    }

    // Actualizar pantalla independientemente
    actualizarPantalla(last_voltage, last_current, last_power);
  }

  // 2. ENVIAR WEBHOOK
  if (currentMillis - previousMillisWebhook >= intervalWebhook) {
    previousMillisWebhook = currentMillis;

    if(WiFi.status() == WL_CONNECTED){
      enviarDatosWebhook(last_voltage, last_current, last_power, last_energy);
    } else {
      Serial.println("WiFi desconectado. Reconectando..."); 
      WiFi.reconnect();
    }
  }
}

// --- FUNCIONES AUXILIARES ---

void actualizarPantalla(float v, float c, float p) {
  display.clearDisplay();
  
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.print("WiFi:");
  display.print(WiFi.status() == WL_CONNECTED ? "OK" : "NO");
  
  display.setCursor(64, 0); 
  display.print("Rly:");
  display.println(digitalRead(RELAY_PIN) ? "ON" : "OFF");

  display.drawLine(0, 10, SCREEN_WIDTH, 10, SSD1306_WHITE);

  display.setCursor(0, 15);
  display.print("Vol: ");
  display.print(v, 1);
  display.println(" V");

  display.setCursor(0, 27);
  display.print("Amp: ");
  display.print(c, 2);
  display.println(" A");

  display.setCursor(0, 42); 
  display.setTextSize(2); 
  display.print(p, 1);
  display.setTextSize(1); 
  display.println(" W");

  display.display();
}

void enviarDatosWebhook(float v, float c, float p, float e) {
  HTTPClient http;
  
  Serial.println("Enviando Webhook...");
  http.begin(webhookUrl);
  http.addHeader("Content-Type", "application/json");

  String payload = "{";
  payload += "\"voltaje\":" + String(v) + ",";
  payload += "\"corriente\":" + String(c) + ",";
  payload += "\"potencia\":" + String(p) + ",";
  payload += "\"energia\":" + String(e) + ",";
  payload += "\"frecuencia\":" + String(last_frequency) + ",";
  payload += "\"fp\":" + String(last_pf) + ",";
  payload += "\"relay_estado\":\"" + String(digitalRead(RELAY_PIN) ? "ON" : "OFF") + "\"";
  payload += "}";

  int httpResponseCode = http.POST(payload);
  if (httpResponseCode > 0) {
    Serial.println("OK: " + String(httpResponseCode));
  } else {
    Serial.println("Error POST: " + String(httpResponseCode));
  }
  http.end();
}

void mostrarMensaje(String linea1, String linea2) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 25);
  display.println(linea1);
  display.setCursor(0, 40);
  display.println(linea2);
  display.display();
}
