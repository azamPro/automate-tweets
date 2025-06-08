import os
import time
import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from dotenv import load_dotenv

# Load credentials
load_dotenv()
EMAIL = os.getenv("TWITTER_EMAIL")
USERNAME = os.getenv("TWITTER_USERNAME")
PASSWORD = os.getenv("TWITTER_PASSWORD")

# Compose tweet
tweet = f"hello"

# Setup WebDriver
driver = webdriver.Chrome()
driver.get("https://x.com/login")
time.sleep(5)

# Step 1: Enter email
driver.find_element(By.NAME, 'text').send_keys(EMAIL)
driver.find_element(By.NAME, 'text').send_keys(Keys.ENTER)
time.sleep(3)

# Step 2: Enter username (if prompted)
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

from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

driver.get("https://x.com/compose/post")

try:
    textarea = WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="tweetTextarea_0"]'))
    )
    textarea.click()
    textarea.send_keys(tweet)
except Exception as e:
    print("Failed to find tweet box:", e)
    driver.quit()
    exit()

# Wait for and click the "Post" button
tweet_button = WebDriverWait(driver, 10).until(
    EC.element_to_be_clickable((By.CSS_SELECTOR, 'button[data-testid="tweetButton"]'))
)
tweet_button.click()


time.sleep(5)
driver.quit()
