import time
import argparse
import re
import json
import platform
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains

def extract_lat_lng(url):
    try:
        pattern = r"@(-?\d+\.\d+),(-?\d+\.\d+)"
        match = re.search(pattern, url)

        if match:
            lat, lng = match.groups()
            return {
                "status": True,
                "latitude": float(lat),
                "longitude": float(lng)
            }
        else:
            return {
                "status": False,
                "error": "Latitude and Longitude not found in the URL"
            }
    except Exception as e:
        return {
            "status": False,
            "error": str(e)
        }

def get_redirected_url(initial_url):
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    options.add_argument("--headless")  # Run in headless mode
    options.add_argument("--disable-blink-features=AutomationControlled")  # Prevent detection

    # Determine OS and set user data path accordingly
    if platform.system() == "Windows":
        options.add_argument("--user-data-dir=C:/Users/DELL/AppData/Local/Google/Chrome/User Data/Default")
    elif platform.system() == "Linux":
        options.add_argument("--user-data-dir=/home/$USER/.config/google-chrome/Default")
    
    driver = webdriver.Chrome(options=options)
    
    try:
        driver.get(initial_url)
        time.sleep(2)

        try:
            accept_button = driver.find_element(By.XPATH, "//button[contains(text(),'Accept all')]")
            ActionChains(driver).move_to_element(accept_button).click().perform()
            time.sleep(2)
        except:
            print("")

        previous_url = driver.current_url

        for attempt in range(4):
            time.sleep(10)
            current_url = driver.current_url

            if current_url != previous_url:
                return current_url

        return json.dumps({"status": False, "error": "Redirection did not occur after 4 attempts"}, indent=4)
    
    finally:
        driver.quit()

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Retrieve the final redirected URL and extract latitude/longitude.")
    parser.add_argument("url", type=str, help="Initial URL to open")
    
    args = parser.parse_args()
    redirected_url = get_redirected_url(args.url)

    if isinstance(redirected_url, str) and "error" in redirected_url:
        print("")  # Output the JSON error response
    else:
        result = extract_lat_lng(redirected_url)
        print(json.dumps(result, indent=4))