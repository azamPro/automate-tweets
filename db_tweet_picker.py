import mysql.connector
import random
from datetime import datetime
import io
import sys
import os
from dotenv import load_dotenv


def setup_stdout():
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    except Exception:
        pass


# === CONFIG ===
use_queue = False  # Toggle to test either queue or static

load_dotenv()

db_config = {
    'host': os.getenv("DB_HOST"),
    'user': os.getenv("DB_USER"),
    'password': os.getenv("DB_PASS"),
    'database': os.getenv("DB_NAME"),
    'charset': os.getenv("DB_CHARSET", "utf8mb4")
}

def get_connection():
    return mysql.connector.connect(**db_config)

def remove_non_bmp(text):
    if not isinstance(text, str):
        return ""
    return ''.join(c for c in text if ord(c) <= 0xFFFF)

# === LOGGING ===
def log_tweet(tweet_id, source, content, status, message):
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO tweet_logs (tweet_id, source, content, status, message, posted_at)
        VALUES (%s, %s, %s, %s, %s, NOW())
    """, (tweet_id, source, content, status, message))
    conn.commit()
    conn.close()


# === GET ONE QUEUED TWEET ===
def get_queued_tweet():
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM queued_tweets WHERE status = 'pending' AND approved = 1 ORDER BY created_at ASC LIMIT 1")
    tweet = cursor.fetchone()
    conn.close()

    return tweet  

def mark_queued_as_posted(tweet_id, content, success=True, message="Tweet marked as posted."):
    conn = get_connection()
    cursor = conn.cursor()

    cursor.execute("UPDATE queued_tweets SET status = 'posted', posted_at = NOW() WHERE id = %s", (tweet_id,))
    conn.commit()

    status = 'success' if success else 'fail'
    log_tweet(tweet_id, 'queue', content, status, message)

    conn.close()
    
def mark_static_as_posted(tweet_id, content, status="success", message="Static tweet marked as posted."):
    conn = get_connection()
    cursor = conn.cursor()

    cursor.execute("UPDATE static_tweets SET posted = TRUE WHERE id = %s", (tweet_id,))
    conn.commit()

    log_tweet(tweet_id, 'static', content, status, message)
    conn.close()



# === GET ALL STATIC TWEETS ===
def get_static_tweets():
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM static_tweets WHERE posted = FALSE ORDER BY created_at ASC")
    tweets = cursor.fetchall()

    # If no unposted tweets, reset all to false and retry
    if not tweets:
        cursor.execute("UPDATE static_tweets SET posted = FALSE")
        conn.commit()

        cursor.execute("SELECT * FROM static_tweets WHERE posted = FALSE ORDER BY created_at ASC")
        tweets = cursor.fetchall()

    conn.close()
    return tweets  # Return full objects, not just content


# === PICK TWEET ENTRY POINT ===
# def pick_tweet():
#     if use_queue:
#         tweet = get_queued_tweet()
#         if tweet:
#             return tweet
#     static_list = get_static_tweets()
#     # to return random tweet from static list
#     # return random.choice(static_list) if static_list else None
#     return static_list[0] if static_list else None

def pick_tweet():
    if use_queue:
        tweet = get_queued_tweet()
        if tweet:
            return tweet, 'queue'

    static_list = get_static_tweets()
    if static_list:
        return static_list[0], 'static'

    return None, None




# === TESTING ===

if __name__ == "__main__":
    setup_stdout()
