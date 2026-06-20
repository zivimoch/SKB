import base64
import hashlib
import hmac
import json
import os
import time
import uuid
import urllib.request

base_url = os.getenv("SKB_BASE_URL", "http://127.0.0.1:8000")
key_id = os.environ["SKB_KEY_ID"]
secret = os.environ["SKB_SECRET"].encode()
path = "/api/v1/integrations/echo"
body = json.dumps({"message": "test-koneksi"}, separators=(",", ":")).encode()
timestamp = str(int(time.time()))
nonce = str(uuid.uuid4())
request_id = str(uuid.uuid4())
body_hash = hashlib.sha256(body).hexdigest()
canonical = "\n".join(["POST", path, "", timestamp, nonce, "", body_hash]).encode()
signature = base64.b64encode(hmac.new(secret, canonical, hashlib.sha256).digest()).decode()

request = urllib.request.Request(
    base_url + path,
    data=body,
    method="POST",
    headers={
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-SKB-Key-Id": key_id,
        "X-SKB-Timestamp": timestamp,
        "X-SKB-Nonce": nonce,
        "X-SKB-Signature": signature,
        "X-Request-Id": request_id,
        "X-SKB-Actor-Id": "user-fiktif-001",
        "X-SKB-Actor-Name": "Pengguna Sandbox",
        "X-SKB-Actor-Role": "Case Manager",
        "X-SKB-Actor-Institution": "Instansi Sandbox",
    },
)

with urllib.request.urlopen(request) as response:
    print(response.status, response.read().decode())
