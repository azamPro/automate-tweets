import json
import os
import time
import random
import datetime
import sys
import io
import requests
from dotenv import load_dotenv
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import unicodedata
from selenium.common.exceptions import WebDriverException
from db_tweet_picker import  mark_queued_as_posted, pick_tweet, mark_static_as_posted, get_connection
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def remove_non_bmp(text):
    return ''.join(c for c in text if ord(c) <= 0xFFFF)

# === LOAD CREDENTIALS ===
load_dotenv()
EMAIL = os.getenv("TWITTER_EMAIL")
USERNAME = os.getenv("TWITTER_USERNAME")
PASSWORD = os.getenv("TWITTER_PASSWORD")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
COOKIE_PATH = os.path.join(BASE_DIR, "cookies.json")

HEADLESS = True        # set to False for visible browser


# ================= START TELEGRAM NOTI ===================

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
EMOJI_LIST = [
    "❤️",  # Red heart
    "✨",  # Sparkles
    "⭐",  # Star
    "☀️",  # Sun
    "☁️",  # Cloud
    "☕",   # Coffee
]

def add_random_emoji(text):
    emoji = random.choice(EMOJI_LIST)
    if "|" in text:
        return f"{text} {emoji}"
    return f"| {text} | {emoji}"


def log(msg):
    now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted = f"[{now}] {msg}"
    print(formatted)
    
    log_path = os.path.join(os.path.dirname(__file__), "tweet_log.txt")
    try:
        with open(log_path, "a", encoding="utf-8") as f:
            f.write(formatted + "\n")
    except Exception as e:
        print(f"File log failed: {e}")

    # Write to database
    try:
        conn = get_connection()
        cursor = conn.cursor()
        cursor.execute("INSERT INTO logs (log_time, message) VALUES (%s, %s)", (now, msg))
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"DB log failed: {e}")


# === SET TWEET CONTENT ===
tweet = None
queued_id = None

picked, source = pick_tweet()

if not picked:
    log("No tweet available.")
    exit()

tweet = picked['content']
tweet_id = picked['id']




def save_cookies():
    cookies = driver.get_cookies()
    with open(COOKIE_PATH, "w", encoding="utf-8") as f:
        json.dump(cookies, f, ensure_ascii=False, indent=2)
    log("Cookies saved successfully.")

def manual_login_and_save_cookies():
    try:
        log("Manual login started...")
        driver.get("https://x.com/login")
        time.sleep(3)

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
            raise Exception("Manual login failed. Still on login or challenge page.")

        log("Manual login successful.")
        save_cookies()

    except Exception as e:
        log(f"Manual login failed: {e}")
        notify_telegram("Login failed. Please check credentials or captcha.")
        driver.quit()
        exit()

def login_with_cookies_or_fallback():
    if os.path.exists(COOKIE_PATH):
        try:
            log("Trying login using saved cookies...")
            driver.get("https://x.com/")
            with open(COOKIE_PATH, "r", encoding="utf-8") as f:
                cookies = json.load(f)
            for cookie in cookies:
                try:
                    driver.add_cookie(cookie)
                except WebDriverException:
                    pass  # skip invalid cookies

            driver.get("https://x.com/home")
            time.sleep(5)

            if "login" in driver.current_url or "challenge" in driver.current_url:
                raise Exception("Cookies failed to login.")

            log("Logged in with cookies.")

        except Exception as e:
            log(f"Cookie login failed: {e}")
            manual_login_and_save_cookies()
    else:
        manual_login_and_save_cookies()

# === LOGIN ===
log("Launching browser...")
# Browser setup
options = webdriver.ChromeOptions()
if HEADLESS:
    options.add_argument("--headless=new")
options.add_argument("--disable-gpu")
options.add_argument("--window-size=1920,1080")
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
driver = webdriver.Chrome(options=options)

login_with_cookies_or_fallback()

# === COMPOSE TWEET ===
log("Opening tweet composer...")



if source == 'queue':
    try:
        driver.get("https://x.com/compose/post")
        textarea = WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
        )
        textarea.click()
        textarea.send_keys(remove_non_bmp(tweet))
        log(f"Tweet inserted: {tweet}")

        # === ACTUALLY POST IT ===
        tweet_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[data-testid="tweetButton"]'))
        )
        tweet_button.click()
        log("Clicked Post button.")
        time.sleep(3)

        page_source = driver.page_source
        if "You already said that" in page_source or "Whoops!" in page_source:
            log("Duplicate queued tweet detected. Will skip.")
            mark_queued_as_posted(queued_id, tweet, success=False, message="Duplicate tweet.")
        else:
            log("Tweet posted successfully.")
            notify_telegram(f"Tweet posted successfully: {tweet}")
            mark_queued_as_posted(queued_id, tweet)
            posted = True

    except Exception as e:
        log(f"Failed to post queued tweet: {e}")
        notify_telegram("Queued tweet failed.")
        mark_queued_as_posted(queued_id, tweet, success=False, message=str(e))

    


# === CLICK POST ===

posted = False
remaining_tweets = []
variant = None

def post_static(picked):
    base_content = picked['content']
    tweet_id = picked['id']

    variations = [base_content] + [f"{base_content} {emoji}" for emoji in EMOJI_LIST]

    for variant in variations:
        log(f"Trying to post: {remove_non_bmp(variant)}")

        try:
            driver.get("https://x.com/compose/post")
            textarea = WebDriverWait(driver, 20).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
            )
            textarea.click()
            textarea.send_keys(remove_non_bmp(variant))


            tweet_button = WebDriverWait(driver, 10).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[data-testid="tweetButton"]'))
            )
            tweet_button.click()
            log("Clicked Post button.")
            time.sleep(3)

            page_source = driver.page_source
            if "You already said that" in page_source or "Whoops!" in page_source:
                log("Duplicate tweet detected. Trying next variant...")
                continue  

            # Success
            log("Tweet posted successfully.")
            notify_telegram(f"Tweet posted successfully: {variant}")
            return True

        except Exception as e:
            log(f"Failed to post variant: {e}")
            continue
        
    mark_static_as_posted(tweet_id, variant, status="success")
    return False



while True:
    picked, source = pick_tweet()

    if not picked or source != 'static':
        log("No more static tweets or wrong source. Stopping.")
        break

    success = post_static(picked)
    if success:
        break  


time.sleep(5)
driver.quit()
log("All done. Browser closed.")
log("=" * 50)
