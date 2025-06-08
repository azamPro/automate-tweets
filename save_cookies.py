import time
import json
import os
from dotenv import load_dotenv
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys

# Load environment variables
load_dotenv()
EMAIL = os.getenv("TWITTER_EMAIL")
USERNAME = os.getenv("TWITTER_USERNAME")
PASSWORD = os.getenv("TWITTER_PASSWORD")

driver = webdriver.Chrome()
driver.get("https://x.com/login")
time.sleep(5)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
COOKIE_PATH = os.path.join(BASE_DIR, "cookies.json")

try:
    # Step 1: Enter email
    driver.find_element(By.NAME, 'text').send_keys(EMAIL)
    driver.find_element(By.NAME, 'text').send_keys(Keys.ENTER)
    time.sleep(3)

    # Step 2 (if needed): Enter username
    try:
        driver.find_element(By.NAME, 'text').send_keys(USERNAME)
        driver.find_element(By.NAME, 'text').send_keys(Keys.ENTER)
        time.sleep(3)
    except:
        pass

    # Step 3: Enter password
    driver.find_element(By.NAME, 'password').send_keys(PASSWORD)
    driver.find_element(By.NAME, 'password').send_keys(Keys.ENTER)
    time.sleep(5)

    # Check if login succeeded
    if "login" in driver.current_url or "challenge" in driver.current_url:
        print("Login failed. Still on login page.")
    else:
        cookies = driver.get_cookies()
        # with open("cookies.json", "w", encoding="utf-8") as f:
        #     json.dump(cookies, f, ensure_ascii=False, indent=2)
        with open(COOKIE_PATH, "w", encoding="utf-8") as f:
            json.dump(cookies, f, ensure_ascii=False, indent=2)
            
        print("Cookies saved successfully.")

except Exception as e:
    print(f"Login failed: {e}")

driver.quit()
