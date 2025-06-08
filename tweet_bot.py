import os
import time
import random
import datetime
from dotenv import load_dotenv
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


# === LOAD CREDENTIALS ===
load_dotenv()
EMAIL = os.getenv("TWITTER_EMAIL")
USERNAME = os.getenv("TWITTER_USERNAME")
PASSWORD = os.getenv("TWITTER_PASSWORD")


# ================= START TELEGRAM NOTI ===================

import requests

BOT_TOKEN = os.getenv("BOT_TOKEN")
CHAT_ID = os.getenv("CHAT_ID")
SEND_TELEGRAM = os.getenv("SEND_TELEGRAM", "False").lower() == "true"

def notify_telegram(message):
    if not SEND_TELEGRAM or not BOT_TOKEN or not CHAT_ID:
        return
    try:
        response = requests.get(
            f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage",
            params={"chat_id": CHAT_ID, "text": message}
        )
        if response.status_code == 200:
            log("Telegram notification sent.")
        else:
            log(f"Telegram failed with status {response.status_code}")
    except Exception as e:
        log(f"Telegram error: {e}")
# ================= END TELEGRAM NOTI ===================

# === CONFIG SECTION ===
USE_RANDOM = True  # Set to False to use static tweet
TWEET_TEXT = "Hello, I’m alive and tweeting!"
TWEET_LIST = [
    "Good morning from the bot!",
    "Another hour, another tweet.",
    "Stay focused, stay sharp.",
    "This tweet is automated. Cool, right?",
    "Tweeting like a machine. Literally."
]


def log(msg):
    now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{now}] {msg}")


# === SET TWEET CONTENT ===
tweet = random.choice(TWEET_LIST) if USE_RANDOM else TWEET_TEXT

log("Launching browser...")
driver = webdriver.Chrome()
driver.get("https://x.com/login")
time.sleep(5)

# === LOGIN SEQUENCE ===
try:
    log("Logging in...")
    driver.find_element(By.NAME, 'text').send_keys(EMAIL)
    driver.find_element(By.NAME, 'text').send_keys(Keys.ENTER)
    time.sleep(3)

    try:
        driver.find_element(By.NAME, 'text').send_keys(USERNAME)
        driver.find_element(By.NAME, 'text').send_keys(Keys.ENTER)
        time.sleep(3)
    except:
        pass

    driver.find_element(By.NAME, 'password').send_keys(PASSWORD)
    driver.find_element(By.NAME, 'password').send_keys(Keys.ENTER)
    time.sleep(5)
    log("Login successful.")
except Exception as e:
    log("Login failed:", e)
    
    driver.quit()
    exit()

# === COMPOSE TWEET ===
driver.get("https://x.com/compose/post")
log("Opening tweet composer...")

try:
    textarea = WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
    )
    textarea.click()
    textarea.send_keys(tweet)
    log(f"Tweet inserted: {tweet}")
except Exception as e:
    log(f"Failed to write tweet: {e}")
    driver.quit()
    exit()

# === CLICK POST ===
try:
    tweet_button = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[data-testid="tweetButton"]'))
    )
    tweet_button.click()
    log("Tweet posted successfully.")
    notify_telegram("Tweet posted successfully.")
except Exception as e:
    log(f"Failed to post tweet: {e}")
    notify_telegram(f"Tweet failed to post: {e}")


time.sleep(5)
driver.quit()
log("All done. Browser closed.")
