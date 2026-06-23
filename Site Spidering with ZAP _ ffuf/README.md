# Site Spidering with OWASP ZAP + ffuf

---

## 🧪 Objectives

By the end of this lab, students will be able to:

- Configure and use :contentReference[oaicite:0]{index=0} for automated website spidering  
- Implement :contentReference[oaicite:1]{index=1} for directory and file fuzzing operations  
- Combine multiple reconnaissance tools for comprehensive web application enumeration  
- Automate spidering and fuzzing tasks using Python scripts  
- Analyze and interpret results from web reconnaissance activities  
- Build structured reports from discovered web assets  

---

## 📌 Prerequisites

Before starting this lab, students should have:

- Basic understanding of HTTP protocol and web applications  
- Familiarity with Linux command line operations  
- Basic Python programming knowledge  
- Understanding of web security fundamentals  

---

## 🖥️ Lab Environment

**Al Nafi Cloud Machine Setup:**  
This lab runs entirely on Al Nafi's pre-configured Linux-based cloud machines.

- Ubuntu 20.04 LTS  
- Minimum 4GB RAM  
- Pre-installed networking tools  
- Click **Start Lab** to begin  
- No manual VM setup required  

---

# ⚙️ Task 1: Environment Setup and Tool Installation

---

## 🔧 Step 1.1: Install System Dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git python3 python3-pip openjdk-11-jdk unzip
🧪 Step 1.2: Install OWASP ZAP
cd /opt
sudo wget https://github.com/zaproxy/zaproxy/releases/download/v2.14.0/ZAP_2_14_0_unix.sh
sudo chmod +x ZAP_2_14_0_unix.sh
sudo ./ZAP_2_14_0_unix.sh -q
sudo ln -s /opt/ZAP_2.14.0/zap.sh /usr/local/bin/zap

Verify installation:

zap -version
⚡ Step 1.3: Install ffuf
wget https://github.com/ffuf/ffuf/releases/download/v2.1.0/ffuf_2.1.0_linux_amd64.tar.gz
tar -xzf ffuf_2.1.0_linux_amd64.tar.gz
sudo mv ffuf /usr/local/bin/
sudo chmod +x /usr/local/bin/ffuf

Verify:

ffuf -h
📚 Step 1.4: Download Wordlists
mkdir -p ~/wordlists
cd ~/wordlists

git clone https://github.com/danielmiessler/SecLists.git
wget https://raw.githubusercontent.com/v0re/dirb/master/wordlists/common.txt
🧱 Step 1.5: Setup Target Web App (DVWA)
sudo apt install -y apache2 mysql-server php php-mysql php-gd libapache2-mod-php

sudo systemctl start apache2
sudo systemctl start mysql

cd /var/www/html
sudo git clone https://github.com/digininja/DVWA.git
sudo chown -R www-data:www-data DVWA/
sudo chmod -R 755 DVWA/

Database setup:

sudo mysql -e "CREATE DATABASE dvwa;"
sudo mysql -e "CREATE USER 'dvwa'@'localhost' IDENTIFIED BY 'password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON dvwa.* TO 'dvwa'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

Configure DVWA:

cd /var/www/html/DVWA/config
sudo cp config.inc.php.dist config.inc.php
sudo sed -i "s/p@ssw0rd/password/g" config.inc.php

👉 Access:
http://localhost/DVWA

🕷️ Task 2: Website Spidering with OWASP ZAP
🚀 Step 2.1: Start ZAP Daemon Mode
zap -daemon -host 0.0.0.0 -port 8080 \
-config api.addrs.addr.name=.* \
-config api.addrs.addr.regex=true &

Check:

curl http://localhost:8080/JSON/core/view/version/
🧠 Step 2.2: ZAP Spider Automation Script

Create file:

nano zap_spider.py
#!/usr/bin/env python3

import requests
import sys
import time

class ZAPSpider:
    def __init__(self, zap_url="http://localhost:8080"):
        self.zap_url = zap_url

    def start_spider(self, target_url):
        url = f"{self.zap_url}/JSON/spider/action/scan/"
        params = {"url": target_url}
        r = requests.get(url, params=params)
        return r.json().get("scan")

    def get_status(self, scan_id):
        url = f"{self.zap_url}/JSON/spider/view/status/"
        r = requests.get(url, params={"scanId": scan_id})
        return int(r.json().get("status"))

    def wait(self, scan_id):
        while True:
            status = self.get_status(scan_id)
            print(f"[+] Spider progress: {status}%")
            if status >= 100:
                break
            time.sleep(5)

    def get_results(self):
        url = f"{self.zap_url}/JSON/spider/view/results/"
        r = requests.get(url)
        return r.json().get("results")

    def save(self, results, file="spider_results.txt"):
        with open(file, "w") as f:
            for r in results:
                f.write(r + "\n")

def main():
    target = sys.argv[1]
    zap = ZAPSpider()

    scan_id = zap.start_spider(target)
    zap.wait(scan_id)

    results = zap.get_results()
    zap.save(results)

    print(f"[+] Total URLs found: {len(results)}")

if __name__ == "__main__":
    main()

Run:

chmod +x zap_spider.py
python3 zap_spider.py http://localhost/DVWA/
📊 Step 2.3: Analyze Results
cat spider_results.txt
wc -l spider_results.txt

grep -i "admin\|config\|login" spider_results.txt
🧨 Task 3: Directory & File Fuzzing with ffuf
📁 Step 3.1: Directory Fuzzing
ffuf -w ~/wordlists/common.txt \
-u http://localhost/DVWA/FUZZ \
-o ffuf_dirs.json \
-of json \
-t 50 \
-mc 200,301,302,403
📂 Step 3.2: File Extension Fuzzing Script
nano file_fuzz.sh
#!/bin/bash

TARGET="http://localhost/DVWA"
WORDLIST="$HOME/wordlists/common.txt"
EXT="php,txt,html,js,css,bak"

ffuf -w $WORDLIST \
-u $TARGET/FUZZ \
-e $EXT \
-o ffuf_files.json \
-of json \
-t 50 \
-mc 200,301,302,403

echo "[+] File fuzzing completed"

Run:

chmod +x file_fuzz.sh
./file_fuzz.sh
🔍 Step 3.3: View Results
cat ffuf_dirs.json | head
grep "status" ffuf_dirs.json
🤖 Task 4: Advanced Automation (Python)
🧠 Step 4.1: Advanced Fuzzer Script
nano advanced_fuzzer.py
#!/usr/bin/env python3

import subprocess
import json
import os

class Fuzzer:
    def __init__(self, target):
        self.target = target

    def run(self, wordlist, out):
        cmd = [
            "ffuf",
            "-w", wordlist,
            "-u", f"{self.target}/FUZZ",
            "-of", "json",
            "-o", out,
            "-t", "50"
        ]
        subprocess.run(cmd)

    def parse(self, file):
        with open(file) as f:
            return json.load(f)

def main():
    target = "http://localhost/DVWA"
    f = Fuzzer(target)

    f.run("~/wordlists/common.txt", "out.json")
    data = f.parse("out.json")

    print("[+] Results loaded:", len(data.get("results", [])))

if __name__ == "__main__":
    main()

Run:

chmod +x advanced_fuzzer.py
python3 advanced_fuzzer.py
🔄 Task 5: Full Recon Automation
⚙️ Step 5.1: Integrated Script
nano web_recon.py
#!/usr/bin/env python3

import subprocess
import requests
import time

class Recon:
    def __init__(self, target):
        self.target = target

    def zap(self):
        print("[+] Starting ZAP spider...")
        subprocess.call(["python3", "zap_spider.py", self.target])

    def ffuf(self):
        print("[+] Running ffuf...")
        subprocess.call([
            "ffuf",
            "-w", "~/wordlists/common.txt",
            "-u", f"{self.target}/FUZZ",
            "-of", "json",
            "-o", "ffuf.json"
        ])

    def run(self):
        self.zap()
        self.ffuf()
        print("[+] Recon complete")

if __name__ == "__main__":
    Recon("http://localhost/DVWA").run()

Run:

python3 web_recon.py
📊 Expected Outcomes

After completing this lab, students should achieve:

50+ URLs discovered via spidering
10+ directories identified via fuzzing
Hidden admin/config paths exposed
JSON result files generated
Automated recon workflow built
⚠️ Troubleshooting
ZAP not starting
pkill -f zap
zap -daemon
ffuf not found
sudo mv ffuf /usr/local/bin/
DVWA not loading
sudo systemctl restart apache2
🎯 Key Takeaways
Spidering maps visible web structure
ffuf reveals hidden endpoints
Automation increases recon efficiency
Combining tools improves coverage
Python enables scalable security testing
🚀 Conclusion

This lab demonstrated how to combine:

OWASP ZAP for crawling and spidering
ffuf for hidden content discovery

Together, they provide a powerful reconnaissance pipeline for web application security testing.

⚠️ Always use these techniques only in authorized environments.
