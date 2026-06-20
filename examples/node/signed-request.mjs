import crypto from 'node:crypto';

const baseUrl = process.env.SKB_BASE_URL ?? 'http://127.0.0.1:8000';
const keyId = process.env.SKB_KEY_ID;
const secret = process.env.SKB_SECRET;
const path = '/api/v1/integrations/echo';
const body = JSON.stringify({ message: 'test-koneksi' });
const timestamp = Math.floor(Date.now() / 1000).toString();
const nonce = crypto.randomUUID();
const requestId = crypto.randomUUID();
const bodyHash = crypto.createHash('sha256').update(body).digest('hex');
const canonical = ['POST', path, '', timestamp, nonce, '', bodyHash].join('\n');
const signature = crypto.createHmac('sha256', secret).update(canonical).digest('base64');

const response = await fetch(baseUrl + path, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-SKB-Key-Id': keyId,
    'X-SKB-Timestamp': timestamp,
    'X-SKB-Nonce': nonce,
    'X-SKB-Signature': signature,
    'X-Request-Id': requestId,
    'X-SKB-Actor-Id': 'user-fiktif-001',
    'X-SKB-Actor-Name': 'Pengguna Sandbox',
    'X-SKB-Actor-Role': 'Case Manager',
    'X-SKB-Actor-Institution': 'Instansi Sandbox',
  },
  body,
});

console.log(response.status, await response.text());
