#!/usr/bin/env python3
"""Exemple de client signé pour /api/network/events (sert à TESTER l'intégration et de modèle pour le relais RADIUS).
Usage : UPL_WEBHOOK_SECRET=... python3 send_event_example.py https://presence.upl.example/api/network/events 2021001 aabbccddeeff"""
import hashlib, hmac, json, os, sys, time, urllib.request
url, username, ap_mac = sys.argv[1:4]
body = json.dumps({"event": "session_start", "username": username, "session_id": "TEST-1", "ip": "10.0.0.50",
                   "ssid": os.environ.get("UPL_SSID", "UPL-WIFI"), "ap_mac": ap_mac, "client_mac": "02:11:22:33:44:55"}).encode()
ts = str(int(time.time()))
sig = hmac.new(os.environ["UPL_WEBHOOK_SECRET"].encode(), ts.encode() + b"." + body, hashlib.sha256).hexdigest()
req = urllib.request.Request(url, data=body, headers={"Content-Type": "application/json", "Accept": "application/json",
                             "X-UPL-Timestamp": ts, "X-UPL-Signature": sig})
print(urllib.request.urlopen(req).read().decode())
