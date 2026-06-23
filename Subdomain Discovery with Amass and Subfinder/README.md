# 🔎 Subdomain Discovery with Amass and Subfinder

---

## 🎯 Objectives

By the end of this lab, students will be able to:

- Understand the importance of subdomain enumeration in web application penetration testing  
- Install and configure Amass and Subfinder tools on a Linux system  
- Perform comprehensive subdomain discovery using multiple techniques  
- Automate subdomain enumeration processes using Python scripting  
- Consolidate and analyze results from multiple tools  
- Export findings to CSV format for further analysis and reporting  
- Identify potential attack surfaces through subdomain mapping  

---

## 📌 Prerequisites

Before starting this lab, students should have:

- Basic understanding of DNS concepts and domain structure  
- Familiarity with Linux command-line interface  
- Basic knowledge of Python programming  
- Understanding of web application security fundamentals  
- Knowledge of CSV file formats and data manipulation  

### 🖥 Technical Requirements

- Al Nafi provides ready-to-use Linux-based cloud machines  
- Click **Start Lab** to access your pre-configured environment  
- No need to build or configure your own virtual machine  

---

## ⚙️ Lab Environment Setup

---

# 🧩 Task 1: Tool Installation and Configuration

---

## 🛠 Subtask 1.1: Install Amass

We will install **Amass**, a powerful subdomain enumeration tool.

:contentReference[oaicite:0]{index=0}

```bash
# Update system packages
sudo apt update

# Install dependencies
sudo apt install -y wget curl git unzip

# Download Amass
wget https://github.com/owasp-amass/amass/releases/download/v4.2.0/amass_Linux_amd64.zip

# Extract files
unzip amass_Linux_amd64.zip

# Move binary to PATH
sudo mv amass_Linux_amd64/amass /usr/local/bin/

# Verify installation
amass version
🛠 Subtask 1.2: Install Subfinder

We will install Subfinder, a fast passive reconnaissance tool.

Subfinder

# Install Go
sudo apt install -y golang-go

# Set environment variables
echo 'export GOPATH=$HOME/go' >> ~/.bashrc
echo 'export PATH=$PATH:$GOPATH/bin' >> ~/.bashrc
source ~/.bashrc

# Install Subfinder
go install -v github.com/projectdiscovery/subfinder/v2/cmd/subfinder@latest

# Verify installation
subfinder -version
🐍 Subtask 1.3: Install Python Dependencies
sudo apt install -y python3 python3-pip

pip3 install pandas requests dnspython
📁 Subtask 1.4: Create Working Directory
mkdir ~/subdomain_lab
cd ~/subdomain_lab

mkdir results scripts temp
🌐 Task 2: Subdomain Discovery with Amass
🔍 Subtask 2.1: Basic Enumeration
cd ~/subdomain_lab/results

amass enum -d example.com -o amass_basic_results.txt

cat amass_basic_results.txt | head -20
⚡ Subtask 2.2: Advanced Enumeration
amass enum -passive -d example.com -o amass_passive_results.txt

amass enum -passive -d example.com -src -o amass_sources_results.txt

amass enum -brute -d example.com -o amass_brute_results.txt
📊 Subtask 2.3: Analyze Results
echo "Basic: $(wc -l < amass_basic_results.txt)"
echo "Passive: $(wc -l < amass_passive_results.txt)"
echo "Brute: $(wc -l < amass_brute_results.txt)"

cat amass_*.txt | sort -u > amass_combined_results.txt
echo "Total Unique: $(wc -l < amass_combined_results.txt)"
🌍 Task 3: Subdomain Discovery with Subfinder
🔍 Subtask 3.1: Basic Enumeration
subfinder -d example.com -o subfinder_basic_results.txt

cat subfinder_basic_results.txt | head -20
🛰 Subtask 3.2: Source-Based Enumeration
subfinder -ls

subfinder -d example.com -sources crtsh,virustotal,dnsdumpster -o subfinder_sources_results.txt

subfinder -d example.com -all -o subfinder_all_results.txt
📊 Subtask 3.3: Analyze Results
echo "Basic: $(wc -l < subfinder_basic_results.txt)"
echo "Sources: $(wc -l < subfinder_sources_results.txt)"
echo "All: $(wc -l < subfinder_all_results.txt)"

cat subfinder_*.txt | sort -u > subfinder_combined_results.txt
echo "Total Unique: $(wc -l < subfinder_combined_results.txt)"
🐍 Task 4: Python Automation & CSV Integration
⚙️ Subtask 4.1: Automation Script
cd ~/subdomain_lab/scripts

cat > subdomain_automation.py << 'EOF'
#!/usr/bin/env python3

import subprocess
import pandas as pd
import os
import sys
from datetime import datetime
import socket

class SubdomainDiscovery:
    def __init__(self, domain, output_dir):
        self.domain = domain
        self.output_dir = output_dir
        self.results = []

    def run_amass(self):
        print(f"[+] Running Amass: {self.domain}")
        cmd = f"amass enum -passive -d {self.domain}"
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)

        return [x.strip() for x in result.stdout.splitlines() if x.strip()]

    def run_subfinder(self):
        print(f"[+] Running Subfinder: {self.domain}")
        cmd = f"subfinder -d {self.domain} -silent"
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)

        return [x.strip() for x in result.stdout.splitlines() if x.strip()]

    def check_alive(self, subdomain):
        try:
            socket.gethostbyname(subdomain)
            return True
        except:
            return False

    def process(self, a, b):
        all_subs = list(set(a + b))

        for sub in all_subs:
            self.results.append({
                "subdomain": sub,
                "alive": self.check_alive(sub),
                "source": "mixed",
                "time": str(datetime.now())
            })

    def save(self):
        df = pd.DataFrame(self.results)
        path = os.path.join(self.output_dir, f"{self.domain}.csv")
        df.to_csv(path, index=False)
        print("[+] Saved:", path)

    def run(self):
        a = self.run_amass()
        b = self.run_subfinder()
        self.process(a, b)
        self.save()

if __name__ == "__main__":
    domain = sys.argv[1]
    os.makedirs("../results", exist_ok=True)
    SubdomainDiscovery(domain, "../results").run()
EOF

chmod +x subdomain_automation.py
🚀 Subtask 4.2: Run Script
python3 subdomain_automation.py example.com
📊 Subtask 4.3: Analyze CSV
cd ~/subdomain_lab/results

python3 - << 'EOF'
import pandas as pd

df = pd.read_csv("example.com.csv")

print("Total:", len(df))
print("Alive:", len(df[df["alive"] == True]))
print(df.head())
EOF
🔬 Subtask 4.4: Advanced Analysis
cat > advanced_analysis.py << 'EOF'
import pandas as pd
import socket
import requests
import sys

df = pd.read_csv(sys.argv[1])

alive = df[df["alive"] == True]

print("Alive subdomains:", len(alive))
print(alive["subdomain"].tolist())
EOF
🚀 Subtask 4.5: Run Analysis
python3 advanced_analysis.py example.com.csv
📄 Task 5: Reporting
🧾 Subtask 5.1: Generate Report
cat > report.py << 'EOF'
import pandas as pd

df = pd.read_csv("example.com.csv")

with open("report.txt", "w") as f:
    f.write("SUBDOMAIN REPORT\n")
    f.write(f"Total: {len(df)}\n")
    f.write(f"Alive: {len(df[df['alive']==True])}\n")

print("Report generated")
EOF

python3 report.py
📁 Subtask 5.2: Final Output
ls -lah
cat report.txt
⚠️ Troubleshooting
❌ Installation Issues
sudo apt install snapd
sudo snap install amass
❌ Python Issues
pip3 install --upgrade pandas requests
📚 Key Learning Points
Subdomain enumeration reveals hidden attack surfaces
Passive vs active reconnaissance techniques
Tool chaining improves discovery coverage
Python automation increases efficiency
Reporting is critical in penetration testing workflows
🧠 Conclusion

In this lab, you learned how to:

Use Amass for enumeration
Use Subfinder for passive discovery
Automate workflows using Python
Generate structured security reports

These skills are essential for real-world reconnaissance in penetration testing and red team operations.
