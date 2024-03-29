# Jamf API Call For the Vault
## Description
uses JAMF API to get a list of all computers in the JAMF instance and then processes that data and updates our vault with the information
## To Run
navigate to the folder

build the container with ```docker build -t jamf-api .```
run with ```docker run -v ./:/app --env-file=.env --rm --log-driver=syslog --log-opt syslog-address=udp://localhost:514 --log-opt tag=jamf-api-read jamf-api```

## .env requirements
you'll need to provide a .env file with the following variables
```

JAMF_CLIENT_ID=
JAMF_CLIENT_SECRET=
VAULT_HOST=
VAULT_USER=
VAULT_PASSWORD=
VAULT_DATABASE=
VAULT_PORT=
```

## Create Table to hold this data

```
CREATE TABLE jamf_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deviceName VARCHAR(255),
    ram INT,
    cpuType VARCHAR(255),
    cpuSpeed INT,
    os_name VARCHAR(255),
    os_version VARCHAR(255),
    last_check_in DATETIME,
    mac_address VARCHAR(255),
    device_manufacturer VARCHAR(255),
    device_model VARCHAR(255),
    serial VARCHAR(255) UNIQUE
);
```
## Fix Container
We've had a few situations where JAMFs api acts up and when this script crawls the pages sometimes it gets an error where it sees infinite pages.  to fix this remove all the extranious computer_.json and mobile_.json files. you can remove them all with something like ```rm computer_*.json && rm mobile_*.json```

then just rebuild the container again and run with the crontab command so that everything gets updated.
