#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <PZEM004Tv30.h>

// ============================================================
// PINOUT PARA PZEM-004T-100A-D-P (v1.0) + ESP32 WROOM 32
// ============================================================
// PZEM VCC  -> 5V del ESP32 (VIN)
// PZEM GND  -> GND del ESP32
// PZEM RX   -> GPIO 16 (TX2 del ESP32)
// PZEM TX   -> GPIO 17 (RX2 del ESP32)
// PZEM CF   -> GPIO 4 del ESP32
// ============================================================
// OLED (I2C):
//   VCC -> 3.3V | GND -> GND | SCL -> GPIO 22 | SDA -> GPIO 21
// ============================================================
// Relay:
//   VCC -> 5V | GND -> GND | IN -> GPIO 5
// ============================================================

// --- PINES PZEM-004T v1.0 ---
#define PZEM_RX_PIN 17   // RX del ESP32 (conecta a TX del PZEM)
#define PZEM_TX_PIN 16   // TX del ESP32 (conecta a RX del PZEM)
#define CF_PIN      4    // Pin CF (salida de pulsos de energía del PZEM)

// --- PANTALLA OLED (I2C) ---
#define SCREEN_WIDTH   128
#define SCREEN_HEIGHT  64
#define OLED_RESET     -1
#define SCREEN_ADDRESS 0x3C

// --- RELAY ---
#define RELAY_PIN 5  // Cambiado a GPIO 5 para liberar GPIO 4 para CF

// --- CONFIGURACIÓN DE RED ---
const char* ssid       = "Internet";
const char* password   = "balon100";
const char* webhookUrl = "https://n8n.systemautomatic.xyz/webhook/energia";

// --- INTERVALOS ---
unsigned long previousMillisWebhook = 0;
const long intervalWebhook = 5000;  // 5 segundos

unsigned long previousMillisPZEM = 0;
const long intervalPZEM = 2000;     // 2 segundos

// --- VARIABLES DE MEDICIÓN ---
float last_voltage   = 0.0;
float last_current   = 0.0;
float last_power     = 0.0;
float last_energy    = 0.0;
float last_frequency = 0.0;
float last_pf        = 0.0;

// --- PULSOS CF ---
volatile unsigned long pulseCount    = 0;
unsigned long lastPulseTime          = 0;
unsigned long lastPulseCountDisplay  = 0;

// --- INSTANCIAS ---
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// Usar HardwareSerial 2 para la comunicación con el PZEM
HardwareSerial PZEMSerial(2);

// Crear instancia PZEM004Tv30 usando la librería oficial
// La librería funciona tanto con la V3.0 como con la nueva v1.0
PZEM004Tv30 pzem(PZEMSerial, PZEM_RX_PIN, PZEM_TX_PIN);

// --- PROTOTIPOS ---
void mostrarMensaje(String linea1, String linea2);
void actualizarPantalla(float v, float c, float p, float e);
void enviarDatosWebhook(float v, float c, float p, float e);
void IRAM_ATTR onPulse();

// ============================================================
// ISR: Interrupción para contar pulsos del pin CF
// Cada pulso representa una cantidad de energía consumida.
// ============================================================
void IRAM_ATTR onPulse() {
  pulseCount++;
  lastPulseTime = millis();
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  Serial.println("=========================================");
  Serial.println("Monitor de Energía - PZEM-004T v1.0");
  Serial.println("=========================================");

  // Inicializar comunicación serial con PZEM (la librería lo maneja,
  // pero aseguramos que el HardwareSerial esté configurado)
  PZEMSerial.begin(9600, SERIAL_8N1, PZEM_RX_PIN, PZEM_TX_PIN);

  // Configurar pin CF como entrada con interrupción
  pinMode(CF_PIN, INPUT);
  attachInterrupt(digitalPinToInterrupt(CF_PIN), onPulse, RISING);

  // Inicializar Relay (apagado por defecto — HIGH = relé activado si módulo activo bajo)
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH);

  // Inicializar Pantalla OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("Error: No se detectó pantalla OLED. Revisa I2C."));
    for (;;);  // Bucle infinito si falla
  }
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.display();

  mostrarMensaje("Iniciando Monitor", "PZEM-004T v1.0");
  delay(1500);

  // Conectar a WiFi
  mostrarMensaje("Conectando WiFi...", String(ssid));
  WiFi.begin(ssid, password);

  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Conectado - IP: " + WiFi.localIP().toString());
    mostrarMensaje("WiFi Conectado", WiFi.localIP().toString());
  } else {
    Serial.println("\nError: No se pudo conectar a WiFi");
    mostrarMensaje("Error WiFi", "Modo Offline");
  }
  delay(2000);

  Serial.println("Sistema listo. Leyendo PZEM...");
}

// ============================================================
// LOOP
// ============================================================
void loop() {
  unsigned long currentMillis = millis();

  // -------------------------------------------------------
  // 1. LEER DATOS DEL PZEM-004T USANDO LA LIBRERÍA OFICIAL
  // -------------------------------------------------------
  if (currentMillis - previousMillisPZEM >= intervalPZEM) {
    previousMillisPZEM = currentMillis;

    // La librería PZEM004Tv30 se encarga de toda la comunicación
    // Modbus RTU internamente (trama, CRC, parsing).
    float voltage   = pzem.voltage();
    float current   = pzem.current();
    float power     = pzem.power();
    float energy    = pzem.energy();
    float frequency = pzem.frequency();
    float pf        = pzem.pf();

    // Verificar que las lecturas sean válidas (no NaN)
    if (!isnan(voltage) && !isnan(current) && !isnan(power)) {
      last_voltage   = voltage;
      last_current   = current;
      last_power     = power;
      last_energy    = energy;
      last_frequency = frequency;
      last_pf        = pf;

      // Imprimir al monitor serie
      Serial.println("--- Lectura PZEM-004T v1.0 ---");
      Serial.print("Voltaje:    "); Serial.print(last_voltage, 1);  Serial.println(" V");
      Serial.print("Corriente:  "); Serial.print(last_current, 3);  Serial.println(" A");
      Serial.print("Potencia:   "); Serial.print(last_power, 1);    Serial.println(" W");
      Serial.print("Energía:    "); Serial.print(last_energy, 3);   Serial.println(" kWh");
      Serial.print("Frecuencia: "); Serial.print(last_frequency, 1);Serial.println(" Hz");
      Serial.print("Factor PF:  "); Serial.println(last_pf, 2);
      Serial.print("Pulsos CF:  "); Serial.println(pulseCount);
      Serial.println("-------------------------------");

    } else {
      Serial.println("Error: Lectura PZEM inválida (NaN). Verifica conexiones.");
      Serial.println("  -> VCC=5V, GND, RX->GPIO16, TX->GPIO17");
    }

    // Actualizar pantalla siempre
    actualizarPantalla(last_voltage, last_current, last_power, last_energy);
  }

  // -------------------------------------------------------
  // 2. ENVIAR DATOS POR WEBHOOK
  // -------------------------------------------------------
  if (currentMillis - previousMillisWebhook >= intervalWebhook) {
    previousMillisWebhook = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      enviarDatosWebhook(last_voltage, last_current, last_power, last_energy);
    } else {
      Serial.println("WiFi desconectado. Intentando reconexión...");
      WiFi.reconnect();
    }
  }
}

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

/**
 * Actualiza la pantalla OLED con los valores medidos.
 */
void actualizarPantalla(float v, float c, float p, float e) {
  display.clearDisplay();

  // Barra de estado superior
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.print("WiFi:");
  display.print(WiFi.status() == WL_CONNECTED ? "OK" : "NO");

  display.setCursor(64, 0);
  display.print("Rly:");
  display.print(digitalRead(RELAY_PIN) ? "ON" : "OFF");

  display.drawLine(0, 10, SCREEN_WIDTH, 10, SSD1306_WHITE);

  // Valores de medición
  display.setCursor(0, 14);
  display.print("Vol: ");
  display.print(v, 1);
  display.println(" V");

  display.setCursor(0, 24);
  display.print("Amp: ");
  display.print(c, 3);
  display.println(" A");

  display.setCursor(0, 34);
  display.print("Frq: ");
  display.print(last_frequency, 1);
  display.print("Hz PF:");
  display.print(last_pf, 2);

  // Potencia en tamaño grande
  display.setCursor(0, 46);
  display.setTextSize(2);
  display.print(p, 0);
  display.setTextSize(1);
  display.print(" W");

  // Energía en la esquina inferior derecha
  display.setCursor(80, 56);
  display.print(e, 1);
  display.print("kWh");

  display.display();
}

/**
 * Envía los datos de medición al webhook configurado.
 */
void enviarDatosWebhook(float v, float c, float p, float e) {
  HTTPClient http;

  Serial.println("Enviando datos al Webhook...");
  http.begin(webhookUrl);
  http.addHeader("Content-Type", "application/json");

  // Construir payload JSON con todos los valores
  String payload = "{";
  payload += "\"voltaje\":" + String(v, 1) + ",";
  payload += "\"corriente\":" + String(c, 3) + ",";
  payload += "\"potencia\":" + String(p, 1) + ",";
  payload += "\"energia\":" + String(e, 3) + ",";
  payload += "\"frecuencia\":" + String(last_frequency, 1) + ",";
  payload += "\"factor_potencia\":" + String(last_pf, 2) + ",";
  payload += "\"pulsos_cf\":" + String(pulseCount) + ",";
  payload += "\"relay_estado\":\"" + String(digitalRead(RELAY_PIN) ? "ON" : "OFF") + "\"";
  payload += "}";

  int httpResponseCode = http.POST(payload);
  if (httpResponseCode > 0) {
    Serial.println("Webhook OK: " + String(httpResponseCode));
  } else {
    Serial.println("Webhook Error: " + String(httpResponseCode));
  }
  http.end();
}

/**
 * Muestra un mensaje de dos líneas centrado en la pantalla OLED.
 */
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
