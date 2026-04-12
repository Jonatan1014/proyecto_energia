#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <PZEM004Tv30.h>

// ============================================================
//  CONEXIONES PZEM-004T-100A-D-P (v1.0) con ESP32
// ============================================================
//
//  ┌─────────────────────────────────────────────────────────┐
//  │  PZEM-004T v1.0          ESP32 WROOM 32                │
//  │  ──────────────          ──────────────                 │
//  │  VCC  (pin 5V)  ──────>  VIN ó 5V                      │
//  │  GND            ──────>  GND                            │
//  │  TX   (dato OUT)──────>  GPIO 16 (RX2) ← ESP32 recibe  │
//  │  RX   (dato IN) <──────  GPIO 17 (TX2) → ESP32 envía   │
//  │  CF   (pulsos)  ──────>  GPIO 4                         │
//  └─────────────────────────────────────────────────────────┘
//
//  ⚠️ IMPORTANTE: Sigue las etiquetas del BOARD (placa):
//     - En la placa ESP32, GPIO 16 dice "RX2" → ahí va el TX del PZEM
//     - En la placa ESP32, GPIO 17 dice "TX2" → ahí va el RX del PZEM
//
//  ⚠️ El PZEM necesita AMBAS cosas para funcionar:
//     1. Alimentación 5V en VCC (NO 3.3V)
//     2. Lado AC conectado a 110V/220V (sin AC, el módulo no enciende)
//
// ============================================================

// --- PINES PZEM (USANDO PINES DEFAULT de Serial2) ---
// GPIO 16 = RX2 del ESP32 (aquí RECIBE datos del TX del PZEM)
// GPIO 17 = TX2 del ESP32 (aquí ENVÍA datos al RX del PZEM)
#define PZEM_ESP_RX  16   // ESP32 recibe aquí ← conecta TX del PZEM
#define PZEM_ESP_TX  17   // ESP32 transmite aquí → conecta RX del PZEM
#define CF_PIN        4   // Pin CF del PZEM (pulsos de energía)

// --- PANTALLA OLED (I2C) ---
#define SCREEN_WIDTH   128
#define SCREEN_HEIGHT  64
#define OLED_RESET     -1
#define SCREEN_ADDRESS 0x3C

// --- RELAY ---
#define RELAY_PIN 5

// --- CONFIGURACIÓN DE RED ---
const char* ssid       = "Internet";
const char* password   = "balon100";

// --- ENDPOINTS LOCALES (CAMBIAR IP Y API KEY) ---
// Obtener la IP de la máquina donde instalaste XAMPP si usas red local
const char* apiKey        = "TU_API_KEY_AQUI"; // Sacar del dashboard
const char* webhookUrl    = "http://TU_IP_LOCAL/proyecto_energia/src/public/api/save";
const char* relayCheckUrl = "http://TU_IP_LOCAL/proyecto_energia/src/public/api/relay-status";

// --- INTERVALOS ---
unsigned long previousMillisWebhook = 0;
const long intervalWebhook = 5000;

unsigned long previousMillisPZEM = 0;
const long intervalPZEM = 2000;

// --- VARIABLES DE MEDICIÓN ---
float last_voltage   = 0.0;
float last_current   = 0.0;
float last_power     = 0.0;
float last_energy    = 0.0;
float last_frequency = 0.0;
float last_pf        = 0.0;

// --- PULSOS CF ---
volatile unsigned long pulseCount = 0;
unsigned long lastPulseTime       = 0;

// --- INSTANCIAS ---
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);
PZEM004Tv30* pzem = nullptr;

// Pines UART confirmados tras auto-detección
uint8_t confirmedRX = PZEM_ESP_RX;
uint8_t confirmedTX = PZEM_ESP_TX;

// --- PROTOTIPOS ---
void mostrarMensaje(String linea1, String linea2);
void actualizarPantalla(float v, float c, float p, float e);
void enviarDatosWebhook(float v, float c, float p, float e);
void consultarEstadoRelay();
void IRAM_ATTR onPulse();
bool testModbusRaw(uint8_t rxPin, uint8_t txPin, const char* label);

// ============================================================
// ISR: Interrupción para pulsos CF
// ============================================================
void IRAM_ATTR onPulse() {
  pulseCount++;
  lastPulseTime = millis();
}

// ============================================================
// TEST MODBUS CRUDO: prueba comunicación en una config de pines
// ============================================================
bool testModbusRaw(uint8_t rxPin, uint8_t txPin, const char* label) {
  Serial.print("  Probando ");
  Serial.print(label);
  Serial.print(" (ESP32 RX=GPIO");
  Serial.print(rxPin);
  Serial.print(", TX=GPIO");
  Serial.print(txPin);
  Serial.print(")... ");

  // Re-inicializar Serial2 con estos pines
  Serial2.end();
  delay(50);
  Serial2.begin(9600, SERIAL_8N1, rxPin, txPin);
  delay(100);

  // Limpiar buffer
  while (Serial2.available()) { Serial2.read(); }

  // Probar 3 direcciones Modbus comunes
  // Dir 0x01 (común en V3.0)
  byte cmd01[] = {0x01, 0x04, 0x00, 0x00, 0x00, 0x0A, 0x70, 0x0D};
  // Dir 0xF8 (broadcast, default de fábrica)
  byte cmdF8[] = {0xF8, 0x04, 0x00, 0x00, 0x00, 0x0A, 0x64, 0x64};

  // Intentar dirección 0x01
  Serial2.write(cmd01, sizeof(cmd01));
  Serial2.flush();
  delay(300);

  if (Serial2.available() >= 5) {
    Serial.print("¡RESPUESTA en addr 0x01! Bytes: ");
    while (Serial2.available()) {
      byte b = Serial2.read();
      if (b < 0x10) Serial.print("0");
      Serial.print(b, HEX);
      Serial.print(" ");
    }
    Serial.println();
    return true;
  }

  // Limpiar e intentar dirección 0xF8
  while (Serial2.available()) { Serial2.read(); }
  Serial2.write(cmdF8, sizeof(cmdF8));
  Serial2.flush();
  delay(300);

  if (Serial2.available() >= 5) {
    Serial.print("¡RESPUESTA en addr 0xF8! Bytes: ");
    while (Serial2.available()) {
      byte b = Serial2.read();
      if (b < 0x10) Serial.print("0");
      Serial.print(b, HEX);
      Serial.print(" ");
    }
    Serial.println();
    return true;
  }

  Serial.println("Sin respuesta.");
  return false;
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(1000);  // Esperar a que el ESP32 se estabilice completamente

  Serial.println("\n=========================================");
  Serial.println("Monitor de Energía - PZEM-004T v1.0");
  Serial.println("=========================================");

  // Inicializar Relay
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH);

  // Inicializar pin CF
  pinMode(CF_PIN, INPUT);
  attachInterrupt(digitalPinToInterrupt(CF_PIN), onPulse, RISING);

  // Inicializar Pantalla OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("Error: OLED no detectado."));
    for (;;);
  }
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.display();

  mostrarMensaje("Buscando PZEM...", "Auto-detectando...");

  // ============================================================
  // AUTO-DETECCIÓN DE PINES TX/RX
  // Prueba ambas configuraciones posibles para detectar
  // automáticamente cómo están conectados los cables.
  // ============================================================
  Serial.println("\n--- Auto-detección de conexión UART ---");
  Serial.println("Probando comunicación con el PZEM...\n");

  bool found = false;

  // Config A: Pines por DEFECTO de Serial2 (más común)
  //   ESP32 RX en GPIO 16 ← TX del PZEM
  //   ESP32 TX en GPIO 17 → RX del PZEM
  if (testModbusRaw(16, 17, "Config A (RX=16, TX=17)")) {
    confirmedRX = 16;
    confirmedTX = 17;
    found = true;
    Serial.println("  >>> Config A funciona! <<<\n");
  }

  // Config B: Pines INVERTIDOS (según algunas guías)
  //   ESP32 RX en GPIO 17 ← TX del PZEM
  //   ESP32 TX en GPIO 16 → RX del PZEM
  if (!found && testModbusRaw(17, 16, "Config B (RX=17, TX=16)")) {
    confirmedRX = 17;
    confirmedTX = 16;
    found = true;
    Serial.println("  >>> Config B funciona! <<<\n");
  }

  if (!found) {
    Serial.println("\n╔══════════════════════════════════════════════╗");
    Serial.println("║  PZEM NO DETECTADO EN NINGUNA CONFIGURACIÓN ║");
    Serial.println("╠══════════════════════════════════════════════╣");
    Serial.println("║  Revisa lo siguiente:                       ║");
    Serial.println("║                                             ║");
    Serial.println("║  1. ALIMENTACIÓN 5V:                        ║");
    Serial.println("║     - VCC del PZEM → pin VIN (5V) del ESP32 ║");
    Serial.println("║     - NO usar 3.3V, el UART no funciona     ║");
    Serial.println("║                                             ║");
    Serial.println("║  2. TIERRA COMÚN:                           ║");
    Serial.println("║     - GND del PZEM → GND del ESP32          ║");
    Serial.println("║                                             ║");
    Serial.println("║  3. CABLES DE DATOS:                        ║");
    Serial.println("║     - TX del PZEM → GPIO 16 del ESP32       ║");
    Serial.println("║     - RX del PZEM → GPIO 17 del ESP32       ║");
    Serial.println("║                                             ║");
    Serial.println("║  4. CORRIENTE ALTERNA:                      ║");
    Serial.println("║     - Los terminales AC del PZEM DEBEN      ║");
    Serial.println("║       estar conectados a 110V/220V.         ║");
    Serial.println("║     - Sin AC el módulo NO enciende.         ║");
    Serial.println("║                                             ║");
    Serial.println("║  5. BOBINA CT:                              ║");
    Serial.println("║     - Debe pasar la línea viva por el       ║");
    Serial.println("║       agujero de la bobina toroidal.        ║");
    Serial.println("╚══════════════════════════════════════════════╝");

    mostrarMensaje("PZEM NO ENCONTRADO", "Revisa cableado!");
    Serial.println("\nContinuando en modo degradado (reintentará en loop)...\n");
  }

  // Crear el objeto PZEM con los pines confirmados (o default si no detectó)
  Serial.print("Inicializando PZEM con RX=GPIO");
  Serial.print(confirmedRX);
  Serial.print(", TX=GPIO");
  Serial.println(confirmedTX);

  Serial2.end();
  delay(50);
  pzem = new PZEM004Tv30(Serial2, confirmedRX, confirmedTX);
  delay(500);

  Serial.print("Dirección PZEM: 0x");
  Serial.println(pzem->getAddress(), HEX);

  // Primera lectura de prueba
  float testV = pzem->voltage();
  if (!isnan(testV)) {
    Serial.print("✓ Lectura OK — Voltaje: ");
    Serial.print(testV, 1);
    Serial.println(" V");
    mostrarMensaje("PZEM Conectado!", String(testV, 1) + " V");
  } else {
    Serial.println("✗ Primera lectura NaN");
  }

  // Conectar WiFi
  delay(1000);
  mostrarMensaje("Conectando WiFi...", String(ssid));
  WiFi.begin(ssid, password);

  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Conectado — IP: " + WiFi.localIP().toString());
    mostrarMensaje("WiFi Conectado", WiFi.localIP().toString());
  } else {
    Serial.println("\nError WiFi — Modo Offline");
    mostrarMensaje("Error WiFi", "Modo Offline");
  }
  delay(1500);

  Serial.println("\nSistema listo. Leyendo PZEM cada 2 segundos...\n");
}

// ============================================================
// LOOP
// ============================================================
void loop() {
  unsigned long currentMillis = millis();

  // -------------------------------------------------------
  // 1. LEER DATOS DEL PZEM-004T
  // -------------------------------------------------------
  if (currentMillis - previousMillisPZEM >= intervalPZEM) {
    previousMillisPZEM = currentMillis;

    if (pzem == nullptr) {
      Serial.println("Error: PZEM no inicializado.");
      return;
    }

    float voltage   = pzem->voltage();
    float current   = pzem->current();
    float power     = pzem->power();
    float energy    = pzem->energy();
    float frequency = pzem->frequency();
    float pf        = pzem->pf();

    if (!isnan(voltage)) {
      last_voltage   = voltage;
      last_current   = isnan(current)   ? 0.0 : current;
      last_power     = isnan(power)     ? 0.0 : power;
      last_energy    = isnan(energy)    ? 0.0 : energy;
      last_frequency = isnan(frequency) ? 0.0 : frequency;
      last_pf        = isnan(pf)        ? 0.0 : pf;

      Serial.println("--- Lectura PZEM OK ---");
      Serial.print("Voltaje:    "); Serial.print(last_voltage, 1);   Serial.println(" V");
      Serial.print("Corriente:  "); Serial.print(last_current, 3);   Serial.println(" A");
      Serial.print("Potencia:   "); Serial.print(last_power, 1);     Serial.println(" W");
      Serial.print("Energía:    "); Serial.print(last_energy, 3);    Serial.println(" kWh");
      Serial.print("Frecuencia: "); Serial.print(last_frequency, 1); Serial.println(" Hz");
      Serial.print("Factor PF:  "); Serial.println(last_pf, 2);
      Serial.print("Pulsos CF:  "); Serial.println(pulseCount);
      Serial.println("------------------------");

    } else {
      Serial.println("Lectura NaN — PZEM sin respuesta.");
    }

    actualizarPantalla(last_voltage, last_current, last_power, last_energy);
  }

  // -------------------------------------------------------
  // 2. ENVIAR DATOS POR WEBHOOK Y CONSULTAR RELAY
  // -------------------------------------------------------
  if (currentMillis - previousMillisWebhook >= intervalWebhook) {
    previousMillisWebhook = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      enviarDatosWebhook(last_voltage, last_current, last_power, last_energy);
      consultarEstadoRelay(); // Comprobar estado del relay después de enviar datos
    } else {
      Serial.println("WiFi desconectado. Reconectando...");
      WiFi.reconnect();
    }
  }
}

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

void consultarEstadoRelay() {
  HTTPClient http;

  Serial.println("Consultando estado del Relay...");
  http.begin(relayCheckUrl);
  http.addHeader("X-API-KEY", apiKey);

  int httpResponseCode = http.GET();
  if (httpResponseCode > 0) {
    String response = http.getString();
    // Esperamos un json: {"status":"success","relay":"ON"}
    if (response.indexOf("\"relay\":\"ON\"") > 0) {
      digitalWrite(RELAY_PIN, HIGH);
      Serial.println("Relay establecido a: ON (HIGH)");
    } else if (response.indexOf("\"relay\":\"OFF\"") > 0) {
      digitalWrite(RELAY_PIN, LOW);
      Serial.println("Relay establecido a: OFF (LOW)");
    }
  } else {
    Serial.println("Error al consultar relay: " + String(httpResponseCode));
  }
  http.end();
}

void actualizarPantalla(float v, float c, float p, float e) {
  display.clearDisplay();

  display.setTextSize(1);
  display.setCursor(0, 0);
  display.print("WiFi:");
  display.print(WiFi.status() == WL_CONNECTED ? "OK" : "NO");

  display.setCursor(64, 0);
  display.print("Rly:");
  display.print(digitalRead(RELAY_PIN) ? "ON" : "OFF");

  display.drawLine(0, 10, SCREEN_WIDTH, 10, SSD1306_WHITE);

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

  display.setCursor(0, 46);
  display.setTextSize(2);
  display.print(p, 0);
  display.setTextSize(1);
  display.print(" W");

  display.setCursor(80, 56);
  display.print(e, 1);
  display.print("kWh");

  display.display();
}

void enviarDatosWebhook(float v, float c, float p, float e) {
  HTTPClient http;

  Serial.println("Enviando datos al Webhook...");
  http.begin(webhookUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", apiKey); // <-- Autenticación agregada

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
