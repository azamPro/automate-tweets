import json
import os
import time
import random
import datetime
import sys
import io
from dotenv import load_dotenv
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def remove_non_bmp(text):
    return ''.join(c for c in text if ord(c) <= 0xFFFF)

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
USE_COOKIES = True  # Toggle this to switch between methods
USE_RANDOM = True  # Set to False to use static tweet
TWEET_TEXT = "أستغفرالله العظيم وأتوب إليه"
TWEET_LIST = [
    "| أأتوب | ❤️",
    "| أستغفرالله  | 🤲",
    "| أستغفرالله العظيم  | ✨"
]
EMOJI_LIST = [
    "❤️",  # Red heart
    "💛",  # Yellow heart
    "💚",  # Green heart
    "💙",  # Blue heart
    "💜",  # Purple heart
    "🖤",  # Black heart
    "💔",  # Broken heart
    "✨",  # Sparkles
    "🌙",  # Crescent moon
    "⭐",  # Star
    "☀️",  # Sun
    "☁️",  # Cloud
    "☕",   # Coffee
    "🕊️",  # Dove
    "🕯️",  # Candle
    "✏️",  # Pencil
    "✔️",  # Check mark
    "📿",  # Prayer beads
    "📖",  # Open book
    "📜",  # Scroll
]


def log_safe(msg):
    try:
        log(msg)
    except UnicodeEncodeError:
        log("[Skipped emoji output]")
        


def log(msg):
    now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted = f"[{now}] {msg}"
    print(formatted)
    with open("tweet_log.txt", "a", encoding="utf-8") as f:
        f.write(formatted + "\n")


# === SET TWEET CONTENT ===
tweet = random.choice(TWEET_LIST) if USE_RANDOM else TWEET_TEXT

log("Launching browser...")
driver = webdriver.Chrome()
driver.get("https://x.com/login")
time.sleep(5)

def manual_login():
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
        
        if "login" in driver.current_url or "challenge" in driver.current_url:
            log("Login failed: Still on login page or challenge page.")
            notify_telegram("Login failed. Please check your credentials.")
            driver.quit()
            exit()
        
        log("Login successful.")
    except Exception as e:
        # log("Login failed:", e)
        log(f"Login failed: {e}")
        log("=" * 50)
        driver.quit()
        exit()
        
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
COOKIE_PATH = os.path.join(BASE_DIR, "cookies.json")

# === LOGIN SEQUENCE ===
# if USE_COOKIES and os.path.exists("cookies.json"):
#     try:    
#         log("Using saved cookies to login.")
#         driver.get("https://x.com/")
#         with open("cookies.json", "r", encoding="utf-8") as f:
#             cookies = json.load(f)
#         for cookie in cookies:
#             driver.add_cookie(cookie)
#         driver.get("https://x.com/home")
#         time.sleep(5)
        
#         if "login" in driver.current_url or "challenge" in driver.current_url:
#             raise Exception("Still on login page after cookies.")

#         log("Logged in with cookies.")
        
#     except Exception as e:
#         log(f"Cookie login failed: {e}")
#         manual_login()
# else:
#     manual_login()
  
if USE_COOKIES and os.path.exists(COOKIE_PATH):
    try:
        log("Trying login using saved cookies...")
        driver.get("https://x.com/")
        with open(COOKIE_PATH, "r", encoding="utf-8") as f:
            cookies = json.load(f)
        for cookie in cookies:
            driver.add_cookie(cookie)
        driver.get("https://x.com/home")
        time.sleep(5)

        if "login" in driver.current_url or "challenge" in driver.current_url:
            raise Exception("Still on login page after cookies.")

        log("Logged in with cookies.")
    except Exception as e:
        log(f"Cookie login failed: {e}")
        manual_login()
else:
    manual_login()


# === COMPOSE TWEET ===
# driver.get("https://x.com/compose/post")
log("Opening tweet composer...")

try:
    textarea = WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
    )
    textarea.click()
    # textarea.send_keys(tweet)
    textarea.send_keys(remove_non_bmp(tweet))

    log(f"Tweet inserted: {tweet}")
except Exception as e:
    log(f"Failed to write tweet: {e}")
    log("=" * 50)
    driver.quit()
    exit()

# === CLICK POST ===
MAX_ATTEMPTS = len(TWEET_LIST)
remaining_tweets = TWEET_LIST.copy()
posted = False

while remaining_tweets:
    tweet = random.choice(remaining_tweets)
    # log(f"Trying to post: {tweet}")
    # log_safe(f"Trying to post: {tweet}")
    log_safe(f"Trying to post: {remove_non_bmp(tweet)}")

    try:
        driver.get("https://x.com/compose/post")

        textarea = WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
        )
        textarea.click()
        textarea.send_keys(remove_non_bmp(tweet))

        tweet_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[data-testid="tweetButton"]'))
        )
        tweet_button.click()
        log("Clicked Post button.")
        time.sleep(3)

        # Check for duplicate message error
        page_source = driver.page_source
        if "You already said that" in page_source or "Whoops!" in page_source:
            log("Duplicate tweet detected. Trying another...")
            remaining_tweets.remove(tweet)
            continue

        # Success
        log("Tweet posted successfully.")
        notify_telegram(f"Tweet posted successfully: {tweet}")
        posted = True
        break

    except Exception as e:
        log(f"Failed to post: {e}")
        log("=" * 50)
        remaining_tweets.remove(tweet)

if not posted:
    log("All tweets failed or were duplicates.")
    notify_telegram("All tweet attempts failed or were duplicates.")
    log("=" * 50)


time.sleep(5)
driver.quit()
log("All done. Browser closed.")
log("=" * 50)
