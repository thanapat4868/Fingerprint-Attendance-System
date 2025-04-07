#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <Adafruit_Fingerprint.h>
#include <HardwareSerial.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ST7735.h>

#define RX_PIN 16 // กำหนด RX
#define TX_PIN 17 // กำหนด TX
#define TCH_PIN 4 // Touch Out ของ AS608
#define BUZZER_PIN 13 // กำหนด BUZZER_PIN

// กำหนดขา pin สำหรับจอ TFT
#define TFT_GND  GND
#define TFT_VCC  VCC
#define TFT_SCL  18
#define TFT_SDA  23
#define TFT_RES  22 // เปลี่ยนขา RES เป็นขา 22
#define TFT_DC   2
#define TFT_CS   15
#define TFT_BLK  21

const char* ssid = "kan"; // WiFi SSID
const char* password = "12345678"; // WiFi Password

// สร้าง WebServer object ที่พอร์ต 80
WebServer server(80);
// Set your Static IP address
IPAddress local_IP(192,168,137,100);
// Set your Gateway IP address
IPAddress gateway(192,168,137,1);
IPAddress subnet(255,255,255,0);


// URL ของ API สำหรับลงทะเบียนลายนิ้วมือ
const char* enrollUrl = "http://192.168.179.177/finger/api.php?action=add_fingerprint";
const char* attendanceUrl = "http://192.168.179.177/finger/api.php?action=process_attendance";

HardwareSerial mySerial(2);
Adafruit_Fingerprint finger(&mySerial);

// สร้างออบเจ็กต์สำหรับจอ TFT
Adafruit_ST7735 tft = Adafruit_ST7735(TFT_CS, TFT_DC, TFT_RES);

// Prototype ของฟังก์ชัน
uint8_t enrollFingerprint(uint8_t id);
void setupWiFi();
void handleEnrollRequest();
void handleStatusRequest();
void handleScanRequest();
void handleCorsPreflight();
void sendRegisterSuccessToServer(const String& stu_id);
void sendAttendanceToServer(int stu_id, const String& class_id);
int getFingerprintID();
void playSuccessTone();
void printToTFT(const String& message);
void startFingerprintScan();
uint8_t deleteFingerprint(uint8_t id);
void handleDeleteRequest();
void handleNotFound();

String currentClassId;

void setupWiFi() {
    if (!WiFi.config(local_IP, gateway, subnet)) {
        Serial.println("STA Failed to configure");
    }

    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(1000);
        Serial.println("Connecting to WiFi...");
        printToTFT("Connecting to WiFi...");
    }
    Serial.println("Connected to WiFi");
    printToTFT("Connected to WiFi");

    // แสดง IP ของ ESP32
    String ipAddress = WiFi.localIP().toString();
    Serial.println("ESP32 IP Address: " + ipAddress);
    printToTFT("IP: " + ipAddress);
}

void setup() {
    Serial.begin(115200);
    mySerial.begin(57600, SERIAL_8N1, RX_PIN, TX_PIN);

    pinMode(TCH_PIN, INPUT);
    pinMode(BUZZER_PIN, OUTPUT);

    ledcSetup(0, 1000, 8);
    ledcAttachPin(BUZZER_PIN, 0);

    tft.initR(INITR_BLACKTAB);
    tft.setRotation(0);
    tft.fillScreen(ST77XX_BLACK);
    tft.setTextColor(ST77XX_WHITE);
    tft.setTextSize(2);

    printToTFT("Initializing...");

    setupWiFi();

    Serial.println("Initializing fingerprint sensor...");
    printToTFT("Initializing fingerprint sensor...");
    finger.begin(57600);
    if (finger.verifyPassword()) {
        Serial.println("Fingerprint sensor detected and initialized.");
        printToTFT("Fingerprint sensor detected and initialized.");
    } else {
        Serial.println("Failed to detect fingerprint sensor. Check wiring!");
        printToTFT("Failed to detect fingerprint sensor. Check wiring!");
        while (1) delay(1000);
    }

    Serial.println("Reading sensor details...");
    printToTFT("Reading sensor details...");
    Serial.print("Sensor capacity: ");
    Serial.println(finger.capacity);
    printToTFT("Sensor capacity: " + String(finger.capacity));

    server.on("/enroll", HTTP_POST, handleEnrollRequest);
    server.on("/status", HTTP_GET, handleStatusRequest);
    server.on("/scan", HTTP_POST, handleScanRequest);
    server.on("/enroll", HTTP_OPTIONS, handleCorsPreflight);
    server.on("/status", HTTP_OPTIONS, handleCorsPreflight);
    server.on("/scan", HTTP_OPTIONS, handleCorsPreflight);
    server.on("/delete", HTTP_POST, handleDeleteRequest);
    server.on("/delete", HTTP_OPTIONS, handleCorsPreflight);

    // เพิ่ม default handler สำหรับเส้นทางที่ไม่ได้กำหนด
    server.onNotFound(handleNotFound);

    server.begin();
    Serial.println("HTTP server started");
    printToTFT("HTTP server started");
}

void addCorsHeaders() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.sendHeader("Access-Control-Allow-Methods", "POST, GET, OPTIONS");
    server.sendHeader("Access-Control-Allow-Headers", "Content-Type");
}

void handleScanRequest() {
    addCorsHeaders(); // เพิ่ม CORS Header

    if (server.hasArg("plain") == false) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing request body\"}");
        return;
    }

    String postBody = server.arg("plain");
    Serial.println("Received request body: " + postBody);
    printToTFT("Received: " + postBody);

    // แปลง JSON
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, postBody);

    if (error) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Invalid JSON format\"}");
        Serial.println("Error parsing JSON: " + String(error.c_str()));
        printToTFT("JSON Error: " + String(error.c_str()));
        return;
    }

    currentClassId = doc["class_id"].as<String>();

    // เรียกใช้ฟังก์ชันสแกนลายนิ้วมือ
    Serial.println("✋ Please scan your fingerprint.");
    printToTFT("Please scan your fingerprint.");
    server.send(200, "application/json", "{\"success\":true,\"message\":\"Please scan your fingerprint.\"}");

    // เริ่มการสแกนลายนิ้วมือ
    startFingerprintScan();
}

void startFingerprintScan() {
    if (digitalRead(TCH_PIN) == HIGH) {
        Serial.println("✋ Please scan your fingerprint.");
        printToTFT("Please scan your fingerprint.");

        int result = getFingerprintID(); // ตรวจสอบลายนิ้วมือและรับค่า stu_id

        if (result >= 0) {
            Serial.print("🔍 Fingerprint ID found: ");
            Serial.println(result);
            printToTFT("Fingerprint ID found: " + String(result));
            playSuccessTone(); // เล่นเสียง buzzer เมื่อพบลายนิ้วมือ

            // ส่งค่า stu_id ที่ได้จากการเปรียบเทียบลายนิ้วมือไปยัง API
            sendAttendanceToServer(result, currentClassId);

            // รอจนกว่าจะไม่มีนิ้วบนเซ็นเซอร์
            while (digitalRead(TCH_PIN) == HIGH) {
                delay(100);
            }
        }
    }
    delay(100); // รอ 100 มิลลิวินาทีก่อนตรวจสอบอีกครั้ง
}

void handleCorsPreflight() {
    addCorsHeaders();
    server.send(204);
}

void loop() {
    server.handleClient(); // จัดการคำขอ HTTP จาก WebServer

    if (digitalRead(TCH_PIN) == HIGH) {
        Serial.println("✋ Please scan your fingerprint.");
        printToTFT("Please scan your fingerprint.");

        int result = getFingerprintID(); // ตรวจสอบลายนิ้วมือและรับค่า stu_id

        if (result >= 0) {
            Serial.print("🔍 Fingerprint ID found: ");
            Serial.println(result);
            printToTFT("Fingerprint ID found: " + String(result));
            playSuccessTone(); // เล่นเสียง buzzer เมื่อพบลายนิ้วมือ

            // ส่งค่า stu_id ที่ได้จากการเปรียบเทียบลายนิ้วมือไปยัง API
            sendAttendanceToServer(result, currentClassId);

            // รอจนกว่าจะไม่มีนิ้วบนเซ็นเซอร์
            while (digitalRead(TCH_PIN) == HIGH) {
                delay(100);
            }
        }
    }
    delay(100); // รอ 100 มิลลิวินาทีก่อนตรวจสอบอีกครั้ง
}

void sendAttendanceToServer(int stu_id, const String& class_id) {
    HTTPClient http;
    String serverUrl = attendanceUrl;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");

    DynamicJsonDocument doc(256);
    doc["stu_id"] = stu_id;
    doc["class_id"] = class_id;

    String requestBody;
    serializeJson(doc, requestBody);

    int httpResponseCode = http.POST(requestBody);

    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.println("Attendance : " + response);
        printToTFT("Attendance : " + response);
    } else {
        Serial.println("Error sending data to server. HTTP response code: " + String(httpResponseCode));
        printToTFT("Error sending data to server. HTTP response code: " + String(httpResponseCode));
    }

    http.end();
}

void handleEnrollRequest() {
    addCorsHeaders(); // เพิ่ม CORS Header

    if (server.hasArg("plain") == false) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing request body\"}");
        return;
    }

    String postBody = server.arg("plain");
    Serial.println("\n\nAttendance:\n" + postBody + "\n\n");
    printToTFT("\nAttendance:\n" + postBody + "\n");    

    // แปลง JSON
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, postBody);

    if (error) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Invalid JSON format\"}");
        Serial.println("Error parsing JSON: " + String(error.c_str()));
        printToTFT("JSON Error: " + String(error.c_str()));
        return;
    }

    String stu_id = doc["stu_id"];
    if (stu_id.isEmpty()) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing stu_id\"}");
        return;
    }

    Serial.println("Enrolling fingerprint for student ID: " + stu_id);
    printToTFT("Enroll for ID: " + stu_id);
    uint8_t result = enrollFingerprint(stu_id.toInt());

    if (result == FINGERPRINT_OK) {
        server.send(200, "application/json", "{\"success\":true,\"message\":\"Fingerprint enrolled successfully\",\"fingerprint_data\":\"Register Success\"}");
        sendRegisterSuccessToServer(stu_id); // ส่งข้อมูลไปที่เซิร์ฟเวอร์
        playSuccessTone(); // เล่นเสียง buzzer เมื่อการลงทะเบียนสำเร็จ
    } else {
        server.send(500, "application/json", "{\"success\":false,\"message\":\"Enrollment failed\"}");
    }
}

void handleStatusRequest() {
    addCorsHeaders(); // เพิ่ม CORS Header

    DynamicJsonDocument response(128);
    response["sensor_status"] = finger.verifyPassword() ? "OK" : "NOT DETECTED";
    response["capacity"] = finger.capacity;

    String jsonResponse;
    serializeJson(response, jsonResponse);

    server.send(200, "application/json", jsonResponse);
    Serial.println("Status request served.");
    printToTFT("Status served.");
}

uint8_t enrollFingerprint(uint8_t id) {
    printToTFT("Starting enrollment process...");
    int p = -1;

    // วางนิ้วครั้งที่ 1
    Serial.println("Place your finger on the sensor.");
    printToTFT("Place your finger.");
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        if (p == FINGERPRINT_NOFINGER) {
            Serial.println("No finger detected.");
            printToTFT("No finger detected.");
            delay(100);
            continue;
        } else if (p == FINGERPRINT_OK) {
            Serial.println("Image taken successfully.");
            printToTFT("Image captured.");
        } else {
            Serial.println("Failed to capture image, error: " + String(p));
            printToTFT("Capture error: " + String(p));
            return p;
        }
    }

    p = finger.image2Tz(1);
    if (p != FINGERPRINT_OK) {
        Serial.println("Failed to convert image to template, error: " + String(p));
        printToTFT("Conversion error: " + String(p));
        return p;
    }

    Serial.println("First image taken and converted to template.");
    printToTFT("First image OK.");

    // รอจนกว่าจะไม่มีนิ้วบนเซ็นเซอร์
    Serial.println("Remove your finger.");
    printToTFT("Remove finger.");
    while (digitalRead(TCH_PIN) == HIGH) {
        delay(100);
    }

    // รอจนกว่าจะมีนิ้ววางบนเซ็นเซอร์อีกครั้ง
    Serial.println("Place the same finger again.");
    printToTFT("Place finger again.");
    while (digitalRead(TCH_PIN) == LOW) {
        delay(100);
    }

    // วางนิ้วครั้งที่ 2
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        if (p == FINGERPRINT_NOFINGER) {
            Serial.println("No finger detected.");
            printToTFT("No finger detected.");
            delay(100);
            continue;
        } else if (p == FINGERPRINT_OK) {
            Serial.println("Second image taken successfully.");
            printToTFT("Second image OK.");
        } else {
            Serial.println("Failed to capture second image, error: " + String(p));
            printToTFT("Second capture error: " + String(p));
            return p;
        }
    }

    p = finger.image2Tz(2);
    if (p != FINGERPRINT_OK) {
        Serial.println("Failed to convert second image to template, error: " + String(p));
        printToTFT("Second conversion error: " + String(p));
        return p;
    }

    Serial.println("Second image taken and converted to template.");
    printToTFT("Second image OK.");

    p = finger.createModel();
    if (p != FINGERPRINT_OK) {
        Serial.println("Failed to create model, error: " + String(p));
        printToTFT("Model creation error: " + String(p));
        return p;
    }

    Serial.println("Model created successfully.");
    printToTFT("Model created.");

    p = finger.storeModel(id);
    if (p != FINGERPRINT_OK) {
        Serial.println("Failed to store model, error: " + String(p));
        printToTFT("Store error: " + String(p));
    } else {
        Serial.println("Fingerprint model stored successfully.");
        printToTFT("Model stored.");
    }

    // รอจนกว่าจะไม่มีนิ้วบนเซ็นเซอร์
    Serial.println("Remove your finger.");
    printToTFT("Remove finger.");
    while (digitalRead(TCH_PIN) == HIGH) {
        delay(100);
    }

    return p == FINGERPRINT_OK ? FINGERPRINT_OK : p;
}

void sendRegisterSuccessToServer(const String& stu_id) {
    HTTPClient http;
    http.begin(enrollUrl);  // ใช้ URL ของ API ที่กำหนดไว้
    http.addHeader("Content-Type", "application/json");

    DynamicJsonDocument doc(256);
    doc["stu_id"] = stu_id;
    doc["fingerprint_data"] = "Register Success";

    String requestBody;
    serializeJson(doc, requestBody);

    int httpResponseCode = http.POST(requestBody);

    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.println("Server response: " + response);
        printToTFT("Enroll: " + response);
    } else {
        Serial.println("Error sending data to server. HTTP response code: " + String(httpResponseCode));
        printToTFT("Send error: " + String(httpResponseCode));
    }

    http.end();
}

int getFingerprintID() {
    uint8_t p = finger.getImage();
    if (p != FINGERPRINT_OK) return -1;

    p = finger.image2Tz();
    if (p != FINGERPRINT_OK) return -1;

    p = finger.fingerFastSearch();
    if (p == FINGERPRINT_OK) {
        return finger.fingerID;  // คืนค่า ID ของลายนิ้วมือที่พบ
    } else {
        Serial.println("❌ Fingerprint not match");
        printToTFT("Fingerprint not match");
        return -1;
    }
}

void playSuccessTone() {
    tone(BUZZER_PIN, 2200, 200); // เปิดเสียง buzzer ที่ความถี่ 2200 Hz เป็นเวลา 200 มิลลิวินาที
    delay(200); // รอให้เสียงเล่นจบ
    noTone(BUZZER_PIN); // ปิดเสียง buzzer
}

void printToTFT(const String& message) {
    tft.fillScreen(ST77XX_BLACK); // ล้างหน้าจอด้วยสีดำ

    // ตั้งค่าขนาดตัวอักษร
    tft.setTextSize(1.3); // ขนาดตัวอักษร 1.3 (สามารถปรับเปลี่ยนได้ตามต้องการ)
    tft.setTextColor(ST77XX_WHITE); // ตั้งค่าสีตัวอักษรเป็นสีขาว

    // กรองข้อความที่ไม่ต้องการ
    String filteredMessage = message;
    filteredMessage.replace("<br />", "\n");
    filteredMessage.replace("<b>", "");
    filteredMessage.replace("</b>", "");
    filteredMessage.replace("&gt;", ">");
    filteredMessage.replace("&lt;", "<");
    filteredMessage.replace("&amp;", "&");

    // แบ่งข้อความเป็นบรรทัด
    String line = "";
    int lineCount = 0;
    std::vector<String> lines;
    for (int i = 0; i < filteredMessage.length(); i++) {
        if (line.length() >= 10 && filteredMessage[i] == ' ') {
            lines.push_back(line);
            lineCount++;
            line = "";
        } else {
            line += filteredMessage[i];
        }
    }
    if (line.length() > 0) {
        lines.push_back(line);
        lineCount++;
    }

    // คำนวณตำแหน่งให้อยู่กึ่งกลาง
    int16_t y = (tft.height() - (lineCount * 16)) / 2; // 16 คือความสูงของตัวอักษรขนาด 1.3 พร้อมระยะห่างระหว่างบรรทัด

    // พิมพ์ข้อความ
    for (int i = 0; i < lines.size(); i++) {
        int16_t x = (tft.width() - (lines[i].length() * 6)) / 2; // 6 คือความกว้างของตัวอักษรขนาด 1
        tft.setCursor(x, y + (i * 16)); // 16 คือความสูงของตัวอักษรขนาด 1.3 พร้อมระยะห่างระหว่างบรรทัด
        tft.println(lines[i]);
    }
}

// เพิ่มฟังก์ชัน deleteFingerprint
uint8_t deleteFingerprint(uint8_t id) {
    uint8_t p = finger.deleteModel(id);
    if (p == FINGERPRINT_OK) {
        Serial.println("Fingerprint deleted successfully.");
        printToTFT("Fingerprint deleted.");
    } else {
        Serial.println("Failed to delete fingerprint, error: " + String(p));
        printToTFT("Delete error: " + String(p));
    }
    return p;
}

// เรียกใช้ฟังก์ชัน deleteFingerprint ในส่วนที่ต้องการ
void handleDeleteRequest() {
    addCorsHeaders(); // เพิ่ม CORS Header

    if (server.hasArg("plain") == false) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing request body\"}");
        return;
    }

    String postBody = server.arg("plain");
    Serial.println("Received request body: " + postBody);
    printToTFT("Received: " + postBody);

    // แปลง JSON
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, postBody);

    if (error) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Invalid JSON format\"}");
        Serial.println("Error parsing JSON: " + String(error.c_str()));
        printToTFT("JSON Error: " + String(error.c_str()));
        return;
    }

    uint8_t stu_id = doc["stu_id"];
    if (stu_id == 0) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing stu_id\"}");
        return;
    }

    Serial.println("Deleting fingerprint for student ID: " + String(stu_id));
    printToTFT("Delete for ID: " + String(stu_id));
    uint8_t result = deleteFingerprint(stu_id);

    if (result == FINGERPRINT_OK) {
        server.send(200, "application/json", "{\"success\":true,\"message\":\"Fingerprint deleted successfully\"}");
    } else {
        server.send(500, "application/json", "{\"success\":false,\"message\":\"Deletion failed\"}");
    }
}

void handleNotFound() {
    addCorsHeaders();
    server.send(404, "application/json", "{\"success\":false,\"message\":\"Not Found\"}");
}