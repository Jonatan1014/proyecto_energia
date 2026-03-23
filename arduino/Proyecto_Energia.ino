#include <WiFi.h>
#include <HTTPClient.h>
#include <PZEM004Tv30.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// --- CONFIGURACIÓN DE PINES ---
// PZEM-004T
#define PZEM_RX_PIN 16
#define PZEM_TX_PIN 17

// Pantalla OLED (I2C)
#define SCREEN_WIDTH 128 
#define SCREEN_HEIGHT 64 
#define OLED_RESET -1  // Pin de reset (o -1 si comparte el pin reset del Arduino)
#define SCREEN_ADDRESS 0x3C // Dirección I2C común para pantallas SSD1306

// Relay
#define RELAY_PIN 4 

// --- CONFIGURACIÓN DE RED ---
// ¡IMPORTANTE!: Cambia estos valores por los de tu red y tu webhook
const char* ssid = "TU_NOMBRE_WIFI";
const char* password = "TU_CONTRASEÑA_WIFI";
const char* webhookUrl = "TU_URL_DEL_WEBHOOK"; // Pega aquí tu URL de Webhook (ej. Make, Zapier, IFTTT)

// --- VARIABLES GLOBALES ---
unsigned long previousMillis = 0;
const long interval = 5000; // Intervalo de 5 segundos para enviar datos al webhook

unsigned long previousMillisPZEM = 0;
const long intervalPZEM = 2000; // Intervalo de 2 segundos para leer el sensor y actualizar pantalla

float last_voltage = 0.0;
float last_current = 0.0;
float last_power = 0.0;
float last_energy = 0.0;

// Inicialización de objetos
/* 
   HardwareSerial 2 se usa por defecto en pines 16 y 17 en muchos ESP32,
   pero lo definimos explícitamente para mayor claridad.
   PZEMRX -> TX2 (GPIO 17)
   PZEMTX -> RX2 (GPIO 16)
*/
PZEM004Tv30 pzem(Serial2, PZEM_RX_PIN, PZEM_TX_PIN);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

void mostrarMensaje(String linea1, String linea2); // Prototipo
void actualizarPantalla(float v, float c, float p); // Prototipo
void enviarDatosWebhook(float v, float c, float p, float e); // Prototipo

void setup() {
  Serial.begin(115200);

  // Inicializar Relay
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH); // Encender relay por defecto (ajustar según tu lógica)

  // Inicializar Pantalla OLED
  // SSD1306_SWITCHCAPVCC = generate display voltage from 3.3V internal
  if(!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("Fallo en la asignación de memoria del SSD1306 - Revisa conexiones I2C"));
    for(;;); // Detener ejecución si falla la pantalla
  }
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.display();
  
  // Mostrar mensaje de inicio
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

  // 1. Leer datos del sensor PZEM cada 'intervalPZEM' (2 segundos)
  // Al leer demasiado rápido (sin pausas), el sensor se "bloquea" y devuelve 0 o NaN
  if (currentMillis - previousMillisPZEM >= intervalPZEM) {
    previousMillisPZEM = currentMillis;

    float voltage = pzem.voltage();
    float current = pzem.current();
    float power = pzem.power();
    float energy = pzem.energy();
    
    // Verificar si los datos son válidos (no son NaN)
    if (isnan(voltage)) {
      Serial.println("Error leyendo sensor PZEM (revisar cables RX/TX o energía AC)");
      // Puedes descomentar la línea de abajo si quieres forzar a que muestre 0 en error
      // last_voltage = 0.0; last_current = 0.0; last_power = 0.0; last_energy = 0.0;
    } else {
      // Guardar lecturas válidas
      last_voltage = voltage;
      last_current = current;
      last_power = power;
      last_energy = energy;
      
      Serial.print("Lectura OK - V: "); Serial.print(last_voltage); 
      Serial.print("V, I: "); Serial.print(last_current); 
      Serial.print("A, P: "); Serial.print(last_power); Serial.println("W");
    }

    // 2. Actualizar pantalla OLED con los últimos datos válidos
    actualizarPantalla(last_voltage, last_current, last_power);
  }

  // 3. Enviar datos al Webhook cada 5 segundos
  if (currentMillis - previousMillis >= interval) {
    previousMillis = currentMillis;

    // Verificar conexión WiFi antes de enviar
    if(WiFi.status() == WL_CONNECTED){
      enviarDatosWebhook(last_voltage, last_current, last_power, last_energy);
    } else {
      Serial.println("WiFi desconectado. Intentando reconectar..."); 
      WiFi.reconnect();
    }
  }
}

// --- FUNCIONES AUXILIARES ---

void actualizarPantalla(float v, float c, float p) {
  display.clearDisplay();
  
  // Encabezado: Estado WiFi (Icono simple: W) y Estado Relay
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.print("WiFi:");
  display.print(WiFi.status() == WL_CONNECTED ? "OK" : "NO");
  
  display.setCursor(64, 0); // Mitad de pantalla
  display.print("Rly:");
  display.println(digitalRead(RELAY_PIN) ? "ON" : "OFF");

  // Línea separadora
  display.drawLine(0, 10, SCREEN_WIDTH, 10, SSD1306_WHITE);

  // Datos de energía
  display.setCursor(0, 15);
  display.print("Vol: ");
  display.print(v, 1);
  display.println(" V");

  display.setCursor(0, 27);
  display.print("Amp: ");
  display.print(c, 2);
  display.println(" A");

  // Potencia (Watts) destacado más grande
  display.setCursor(0, 42); 
  display.setTextSize(2); // Texto doble tamaño
  display.print(p, 1);
  display.setTextSize(1); // Regresar a tamaño normal para la unidad
  display.println(" W");

  display.display();
}

void enviarDatosWebhook(float v, float c, float p, float e) {
  HTTPClient http;
  
  Serial.println("Iniciando envío a Webhook...");
  
  // Iniciar conexión
  http.begin(webhookUrl);
  http.addHeader("Content-Type", "application/json");

  // Crear JSON manualmente
  // Formato: {"voltaje": 110.5, "corriente": 2.1, ...}
  String payload = "{";
  payload += "\"voltaje\":" + String(v) + ",";
  payload += "\"corriente\":" + String(c) + ",";
  payload += "\"potencia\":" + String(p) + ",";
  payload += "\"energia\":" + String(e) + ",";
  payload += "\"relay_estado\":\"" + String(digitalRead(RELAY_PIN) ? "ON" : "OFF") + "\"";
  payload += "}";

  Serial.print("Payload: ");
  Serial.println(payload);

  // Enviar POST request
  int httpResponseCode = http.POST(payload);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Codigo HTTP: " + String(httpResponseCode));
    // Serial.println(response); // Descomentar si quieres ver la respuesta del servidor
  } else {
    Serial.print("Error al enviar POST: ");
    Serial.println(httpResponseCode);
  }

  // Liberar recursos
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
