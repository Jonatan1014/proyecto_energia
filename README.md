# Medidor de Energía Inteligente IoT

## Descripción
Sistema embebido basado en IoT para monitoreo y control del consumo eléctrico residencial.

## Estructura del Proyecto
- `web/`: Aplicación web en PHP (MVC)
- `arduino/`: Código para dispositivo embebido

## Instalación
1. Navegar a `web/`
2. Ejecutar `docker-compose up --build`

## Uso
- Acceder a http://localhost:8080

# Monitor de Energía Inteligente con ESP32

Este proyecto utiliza un ESP32 para leer datos de un sensor PZEM-004T, mostrarlos en una pantalla OLED y enviarlos a un servidor Webhook. Además, controla/monitoriza el estado de un Relay.

## 📦 Materiales Requeridos
1. **ESP32** (NodeMCU-32S)
2. **Sensor PZEM-004T v3.0** (Mide voltaje, corriente, potencia, energía)
3. **Pantalla OLED** (SSD1306 128x64 I2C)
4. **Relay 5V** (Para activar/desactivar la carga)
5. **Convertidor AC-DC 110V a 5V** (Fuente de alimentación)

## 🔌 Diagrama de Conexiones (Pinout)

### 1. Alimentación (Convertidor 110V a 5V)
Para que el ESP32 funcione con la energía de la casa, conecta la salida del convertidor así:
*   **Salida 5V (+)**  -> Pin **VIN** (o 5V) del ESP32.
*   **Salida GND (-)** -> Pin **GND** del ESP32.
*   *Nota: El puerto USB también entrega 5V, pero si usas el convertidor, conéctalo a VIN.*

### 2. Sensor PZEM-004T v3.0
Este sensor usa comunicación Serial (UART). Usaremos `Serial2`.
*   **VCC** -> VIN (5V) del ESP32 (El sensor necesita 5V)
*   **GND** -> GND del ESP32
*   **RX**  -> GPIO **17** (TX2 del ESP32) piel
*   **TX**  -> GPIO **16** (RX2 del ESP32) amarillo
*   *Lado AC:* Conectar a la red 110V y usar la bobina (CT) para medir la corriente de la fase (línea viva).

### 3. Pantalla OLED (I2C)
*   **VCC** -> 3.3V del ESP32
*   **GND** -> GND del ESP32
*   **SCL** -> GPIO **22** gris
*   **SDA** -> GPIO **21** azul

### 4. Módulo Relay 5V
*   **VCC** -> VIN (5V)
*   **GND** -> GND
*   **IN**  -> GPIO **4** negro

## 📚 Librerías Necesarias (Instalar en Arduino IDE)
Ve a *Programa > Incluir Librería > Administrar Bibliotecas* e instala:
1.  **PZEM004Tv30** por *Jakub Mandula*
2.  **Adafruit SSD1306** por *Adafruit*
3.  **Adafruit GFX Library** por *Adafruit*

## ⚙️ Configuración
En el código, recuerda cambiar:
1.  `ssid` y `password` con los datos de tu WiFi.
2.  `serverName` con la URL de tu Webhook (ej. Make, n8n, hoja de cálculo de Google Script).
