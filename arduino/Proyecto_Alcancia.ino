/*
 * Proyecto Alcancía Inteligente IoT - Firmware ESP32
 * Compatible con Monedero Multimoneda (CH-926 / 616)
 * 
 * =====================================================================
 * CONFIGURACIÓN DEL MONEDERO MULTIMONEDA:
 * =====================================================================
 * 
 * Interruptores físicos del monedero:
 *   - NO/NC: Selecciona "NO" (Normalmente Abierto).
 *     -> En reposo el pin de señal está en HIGH (gracias a PULLUP).
 *     -> Cada pulso BAJA el pin a LOW momentáneamente (flanco FALLING).
 *   
 *   - Velocidad de pulso: Selecciona "MEDIUM" (recomendado).
 *     -> "FAST"   = pulsos rápidos  (~20ms entre pulsos, timeout ~150ms)
 *     -> "MEDIUM" = pulsos medianos (~50ms entre pulsos, timeout ~300ms)
 *     -> "SLOW"   = pulsos lentos   (~90ms entre pulsos, timeout ~600ms)
 * 
 * Parámetros internos del monedero (Menú A -> SET):
 *   E = Número de tipos de moneda: 4
 *   H = Cantidad de muestras por moneda (recomendado: 20)
 *   P = Pulsos por tipo de moneda:
 *       Moneda 1 ($100)  -> P1 = 1 pulso
 *       Moneda 2 ($200)  -> P2 = 2 pulsos
 *       Moneda 3 ($500)  -> P3 = 5 pulsos
 *       Moneda 4 ($1000) -> P4 = 10 pulsos
 *   F = Precisión (recomendado: 8)
 * 
 * =====================================================================
 * HARDWARE Y CONEXIONES (ESP32 NodeMCU-32S):
 * =====================================================================
 * 
 * 1. Monedero Multimoneda:
 *    - GND (Monedero) -> GND (ESP32)  * IMPORTANTE: Compartir tierras.
 *    - 12V (Monedero) -> Fuente externa 12V
 *    - COIN_SIGNAL (Cable gris/blanco) -> GPIO 4
 *      * IMPORTANTE: Añadir resistencia PULL-UP externa de 10K Ohms
 *        entre GPIO 4 y 3.3V del ESP32 para eliminar ruido eléctrico.
 *      * Si el monedero envía pulsos de 5V/12V, usar divisor de voltaje
 *        para bajar a 3.3V (máximo tolerado por pines ESP32).
 * 
 * 2. Pantalla OLED 0.96" (I2C - SSD1306):
 *    - VCC -> 3.3V (ESP32)
 *    - GND -> GND (ESP32)
 *    - SDA -> GPIO 21
 *    - SCL -> GPIO 22
 * 
 * 3. Buzzer (Pasivo o Activo):
 *    - VCC / PIN (+) -> GPIO 5
 *    - GND (-) -> GND (ESP32)
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// =====================================================================
// CONFIGURACIÓN - Modifica estos valores según tu proyecto
// =====================================================================

// --- Red Wi-Fi ---
const char* ssid = "Internet";
const char* password = "balon100";

// --- API (Servidor Local XAMPP o Nube) ---
// Cambia a la IP de tu PC en la red local (ej: 192.168.1.X)
const char* api_url_post_coin = "https://alcancia.systemautomatic.xyz/api/alcancia/registrar";
const char* api_url_get_status = "https://alcancia.systemautomatic.xyz/api/alcancia/device-state";

// --- Pines ---
const int PIN_COIN   = 4;
const int PIN_BUZZER = 5;

// --- Pantalla OLED ---
#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// =====================================================================
// CONFIGURACIÓN DEL MONEDERO MULTIMONEDA
// =====================================================================
// Estos valores DEBEN coincidir con lo que programaste en el monedero
// usando el menú de configuración (A -> SET -> E, H, P, F).
//
// Ajusta COIN_PULSE_TIMEOUT según el interruptor de velocidad del monedero:
//   FAST   -> 150 ms
//   MEDIUM -> 300 ms
//   SLOW   -> 600 ms

const unsigned long COIN_PULSE_TIMEOUT = 300;  // MEDIUM (cambia según tu interruptor)
const unsigned long DEBOUNCE_TIME      = 25;   // Anti-rebote entre pulsos (ms)

// Tabla de mapeo: pulsos -> valor en pesos colombianos (COP)
// Debe coincidir con los valores P1, P2, P3... que configuraste en el monedero.
// Formato: { cantidad_pulsos, valor_en_pesos }
struct CoinType {
  int pulses;
  int value;
};

const CoinType COIN_TABLE[] = {
  {  1,   100 },   // P1 = 1 pulso   -> $100 COP
  {  2,   200 },   // P2 = 2 pulsos  -> $200 COP
  {  5,   500 },   // P3 = 5 pulsos  -> $500 COP
  { 10,  1000 },   // P4 = 10 pulsos -> $1000 COP
};
const int COIN_TABLE_SIZE = sizeof(COIN_TABLE) / sizeof(COIN_TABLE[0]);

// =====================================================================
// VARIABLES GLOBALES
// =====================================================================

// --- Conteo de pulsos por interrupción ---
volatile int pulseCount = 0;
volatile unsigned long lastPulseTime = 0;

// --- Estado del ahorro ---
int totalSaved       = 0;
int goalAmount       = 100000;
int pendingSyncCoins = 0;
String currentGoalName = "Meta General";

// --- Control de pantalla ---
unsigned long lastDisplayUpdate   = 0;
unsigned long coinNotificationEnd = 0;
int lastCoinInserted              = 0;
unsigned long lastSyncMillis      = 0;
const unsigned long statusSyncInterval = 60000;

// =====================================================================
// INTERRUPCIÓN - Se ejecuta en cada pulso del monedero
// =====================================================================
// Con el monedero configurado en NO (Normalmente Abierto):
//   - En reposo: pin HIGH (por PULLUP)
//   - Pulso:     pin baja a LOW momentáneamente (flanco FALLING)
// Cada moneda genera N pulsos según el valor P configurado.

void IRAM_ATTR coinInterrupt() {
  unsigned long currentTime = millis();
  if (currentTime - lastPulseTime > DEBOUNCE_TIME) {
    pulseCount++;
    lastPulseTime = currentTime;
  }
}

// =====================================================================
// FUNCIONES DEL BUZZER
// =====================================================================

void beep(int duration = 100) {
  digitalWrite(PIN_BUZZER, HIGH);
  delay(duration);
  digitalWrite(PIN_BUZZER, LOW);
}

void beepCoinAccepted() {
  // Tono corto: moneda aceptada
  beep(80);
}

void beepCoinUnknown() {
  // Doble tono rápido: moneda no reconocida
  beep(50); delay(50);
  beep(50);
}

void beepGoalReached() {
  // Tono celebración: ¡meta alcanzada!
  beep(100); delay(80);
  beep(100); delay(80);
  beep(300);
}

void beepWiFiConnected() {
  beep(100); delay(50);
  beep(100); delay(50);
  beep(300);
}

// =====================================================================
// IDENTIFICAR MONEDA POR CANTIDAD DE PULSOS
// =====================================================================
// Busca en COIN_TABLE cuántos pulsos corresponden a qué moneda.
// Permite ±1 pulso de tolerancia para compensar errores de lectura.
// Retorna el valor en pesos, o 0 si no se reconoce la moneda.

int identifyCoinByPulses(int pulses) {
  // Primero: coincidencia exacta
  for (int i = 0; i < COIN_TABLE_SIZE; i++) {
    if (pulses == COIN_TABLE[i].pulses) {
      return COIN_TABLE[i].value;
    }
  }

  // Segundo: tolerancia ±1 pulso (solo si no hay ambigüedad)
  int matchCount = 0;
  int matchValue = 0;
  for (int i = 0; i < COIN_TABLE_SIZE; i++) {
    if (abs(pulses - COIN_TABLE[i].pulses) <= 1) {
      matchCount++;
      matchValue = COIN_TABLE[i].value;
    }
  }

  if (matchCount == 1) {
    return matchValue;
  }

  // No se reconoció la moneda
  return 0;
}

// =====================================================================
// SETUP - Inicialización del sistema
// =====================================================================

void setup() {
  Serial.begin(115200);
  Serial.println("\n========================================");
  Serial.println("  Alcancía Inteligente IoT - ESP32");
  Serial.println("  Monedero Multimoneda (NO / MEDIUM)");
  Serial.println("========================================");

  // Configurar pines
  // NO (Normalmente Abierto): usamos INPUT_PULLUP
  // En reposo = HIGH, pulso = LOW (flanco FALLING)
  pinMode(PIN_COIN, INPUT_PULLUP);
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_BUZZER, LOW);

  // Interrupción en flanco FALLING (para modo NO)
  // Si tu monedero está en NC (Normalmente Cerrado), cambia a RISING
  attachInterrupt(digitalPinToInterrupt(PIN_COIN), coinInterrupt, FALLING);

  // Inicializar Pantalla OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("Error: No se encontró pantalla OLED SSD1306"));
    for (;;);  // Detener si no hay pantalla
  }
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(1);
  display.setCursor(0, 10);
  display.println("Iniciando Alcancia...");
  display.display();

  // Conectar a Wi-Fi
  WiFi.begin(ssid, password);
  display.println("Conectando WiFi...");
  display.display();
  Serial.print("Conectando a WiFi");

  int wifiAttempts = 0;
  while (WiFi.status() != WL_CONNECTED && wifiAttempts < 40) {
    delay(500);
    Serial.print(".");
    wifiAttempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    beepWiFiConnected();
    Serial.println("\nWiFi Conectado!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());

    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Conectado!");
    display.println(WiFi.localIP());
    display.display();
    delay(2000);

    // Sincronizar estado desde la API
    fetchStatusFromAPI();
  } else {
    Serial.println("\nWiFi no disponible. Modo offline.");
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi NO conectado");
    display.println("Modo Offline");
    display.display();
    delay(2000);
  }

  // Mostrar configuración en Serial
  Serial.println("----------------------------------------");
  Serial.println("Tabla de monedas configurada:");
  for (int i = 0; i < COIN_TABLE_SIZE; i++) {
    Serial.print("  ");
    Serial.print(COIN_TABLE[i].pulses);
    Serial.print(" pulsos -> $");
    Serial.println(COIN_TABLE[i].value);
  }
  Serial.print("Timeout de pulsos: ");
  Serial.print(COIN_PULSE_TIMEOUT);
  Serial.println("ms");
  Serial.println("----------------------------------------");
  Serial.println("Esperando monedas...\n");

  updateDisplay(0);
}

// =====================================================================
// LOOP - Ciclo principal
// =====================================================================
// Lógica de detección de monedas por conteo de pulsos:
//
// 1. La interrupción (coinInterrupt) cuenta cada pulso que llega.
// 2. En el loop, revisamos si ya pasó COIN_PULSE_TIMEOUT ms desde
//    el último pulso. Si sí, significa que la moneda ya terminó de
//    enviar todos sus pulsos.
// 3. Leemos pulseCount, identificamos la moneda por la tabla, y
//    procesamos el valor.

void loop() {
  // ¿Se recibieron pulsos Y ya pasó el timeout desde el último?
  if (pulseCount > 0 && (millis() - lastPulseTime) > COIN_PULSE_TIMEOUT) {

    // Desactivar interrupción para leer de forma segura
    detachInterrupt(digitalPinToInterrupt(PIN_COIN));

    int pulsesReceived = pulseCount;
    pulseCount = 0;  // Resetear para la siguiente moneda

    // Identificar la moneda según los pulsos recibidos
    int coinValue = identifyCoinByPulses(pulsesReceived);

    Serial.print("Pulsos recibidos: ");
    Serial.print(pulsesReceived);

    if (coinValue > 0) {
      // ¡Moneda reconocida!
      Serial.print(" -> Moneda: $");
      Serial.print(coinValue);
      Serial.println(" COP");

      totalSaved += coinValue;
      lastCoinInserted = coinValue;
      coinNotificationEnd = millis() + 3000;  // Notificación visible 3 segundos

      Serial.print("Total ahorrado: $");
      Serial.println(totalSaved);

      beepCoinAccepted();
      updateDisplay(coinValue);
      sendCoinData(coinValue, pulsesReceived);

      // Verificar si alcanzó la meta
      if (totalSaved >= goalAmount && (totalSaved - coinValue) < goalAmount) {
        Serial.println("*** ¡META ALCANZADA! ***");
        beepGoalReached();
      }

    } else {
      // Moneda no reconocida
      Serial.println(" -> MONEDA NO RECONOCIDA");
      beepCoinUnknown();
    }

    Serial.println("---");

    // Reactivar interrupción
    attachInterrupt(digitalPinToInterrupt(PIN_COIN), coinInterrupt, FALLING);
  }

  // Limpiar la notificación flotante después de 3 segundos
  if (lastCoinInserted > 0 && millis() > coinNotificationEnd) {
    lastCoinInserted = 0;
    updateDisplay(0);
  }

  // Sincronizar estado remoto cada minuto para mantener OLED alineada con BD
  if (WiFi.status() == WL_CONNECTED && (millis() - lastSyncMillis) >= statusSyncInterval) {
    fetchStatusFromAPI();
    lastSyncMillis = millis();
  }

  // Pequeña pausa para no saturar el loop
  delay(10);
}

// =====================================================================
// ENVIAR MONEDA A LA API
// =====================================================================

void sendCoinData(int amount, int pulses) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(api_url_post_coin);
    http.addHeader("Content-Type", "application/json");

    // JSON con datos del deposito para trazabilidad en el dashboard
    String jsonPayload = "{";
    jsonPayload += "\"monto\": " + String(amount) + ",";
    jsonPayload += "\"pulsos\": " + String(pulses) + ",";
    jsonPayload += "\"origen\": \"esp32\",";
    jsonPayload += "\"total\": " + String(totalSaved);
    jsonPayload += "}";

    int httpResponseCode = http.POST(jsonPayload);

    if (httpResponseCode > 0) {
      Serial.print("API POST OK (HTTP ");
      Serial.print(httpResponseCode);
      Serial.println(")");
    } else {
      Serial.print("API POST Error: ");
      Serial.println(httpResponseCode);
      pendingSyncCoins += amount;
    }
    http.end();

    // Intentar sincronizar monedas pendientes
    if (pendingSyncCoins > 0 && httpResponseCode > 0) {
      syncPendingCoins();
    }

  } else {
    pendingSyncCoins += amount;
    Serial.print("Sin WiFi. Pendiente por sincronizar: $");
    Serial.println(pendingSyncCoins);
  }
}

// =====================================================================
// SINCRONIZAR MONEDAS PENDIENTES
// =====================================================================

void syncPendingCoins() {
  if (pendingSyncCoins <= 0 || WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(api_url_post_coin);
  http.addHeader("Content-Type", "application/json");

  String jsonPayload = "{\"monto\": " + String(pendingSyncCoins) + ", \"sync\": true}";

  int httpResponseCode = http.POST(jsonPayload);
  if (httpResponseCode > 0) {
    Serial.print("Sincronizado pendiente: $");
    Serial.println(pendingSyncCoins);
    pendingSyncCoins = 0;
  }
  http.end();
}

// =====================================================================
// OBTENER ESTADO DESDE LA API
// =====================================================================

void fetchStatusFromAPI() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(api_url_get_status);
  int httpResponseCode = http.GET();

  if (httpResponseCode == 200) {
    String payload = http.getString();
    StaticJsonDocument<1024> doc;
    DeserializationError err = deserializeJson(doc, payload);

    if (err) {
      Serial.print("Error parseando estado: ");
      Serial.println(err.c_str());
      http.end();
      return;
    }

    JsonVariant data = doc["data"];
    if (data.isNull()) {
      Serial.println("Estado sin campo data");
      http.end();
      return;
    }

    totalSaved = (int)(data["total_ahorrado"] | totalSaved);
    goalAmount = (int)(data["meta_objetivo"] | data["meta_general"] | goalAmount);
    currentGoalName = (const char*)(data["meta_nombre"] | "Meta General");

    Serial.println("Estado sincronizado desde API");
    Serial.print("Total: $");
    Serial.println(totalSaved);
    Serial.print("Meta: ");
    Serial.println(currentGoalName);

    updateDisplay(0);
  } else {
    Serial.print("Error al obtener estado (HTTP ");
    Serial.print(httpResponseCode);
    Serial.println(")");
  }
  http.end();
}

// =====================================================================
// ACTUALIZAR PANTALLA OLED
// =====================================================================

void updateDisplay(int justInsertedCoin) {
  display.clearDisplay();

  // --- Título ---
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("ALCANCIA INTELIGENTE");
  display.drawLine(0, 9, 128, 9, SSD1306_WHITE);

  // --- Total Ahorrado ---
  display.setTextSize(2);
  display.setCursor(0, 14);
  display.print("$");
  display.println(totalSaved);

  // --- Meta ---
  display.setTextSize(1);
  display.setCursor(0, 34);
  display.print("Meta: $");
  display.println(goalAmount);

  // Nombre de meta (truncado para OLED)
  display.setCursor(0, 43);
  String goalLine = currentGoalName;
  if (goalLine.length() > 18) {
    goalLine = goalLine.substring(0, 18);
  }
  display.println(goalLine);

  // --- Barra de progreso ---
  int progress = 0;
  if (goalAmount > 0) {
    progress = (totalSaved * 100) / goalAmount;
    if (progress > 100) progress = 100;
  }
  display.print("Progreso: ");
  display.print(progress);
  display.println("%");

  // Barra visual
  display.drawRect(0, 54, 128, 10, SSD1306_WHITE);
  display.fillRect(1, 55, (126 * progress) / 100, 8, SSD1306_WHITE);

  // --- Notificación de moneda insertada ---
  if (justInsertedCoin > 0) {
    // Fondo negro para la notificación
    display.fillRect(74, 11, 54, 16, SSD1306_BLACK);
    display.drawRect(74, 11, 54, 16, SSD1306_WHITE);

    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(78, 15);
    display.print("+$");
    display.print(justInsertedCoin);
    display.setTextColor(SSD1306_WHITE);
  }

  // --- Indicador WiFi ---
  display.setCursor(120, 0);
  if (WiFi.status() == WL_CONNECTED) {
    display.print("W");  // WiFi conectado
  } else {
    display.print("X");  // Sin WiFi
  }

  // --- Indicador de pendientes ---
  if (pendingSyncCoins > 0) {
    display.setCursor(110, 0);
    display.print("!");
  }

  display.display();
}
