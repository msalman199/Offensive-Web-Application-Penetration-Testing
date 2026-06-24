#!/bin/bash

echo "Testing Vulnerable API:"
curl -s -X POST -H "Content-Type: application/xml" \
  -d @xxe_flag.xml http://localhost:8080/vulnerable_api.php | jq .

echo -e "\nTesting Secure API:"
curl -s -X POST -H "Content-Type: application/xml" \
  -d @xxe_flag.xml http://localhost:8080/secure_api.php | jq .
