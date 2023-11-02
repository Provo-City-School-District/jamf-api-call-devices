# Jamf API Call For the Vault
## To Run
navigate to the folder

build the container with ```docker build -t jamf-api .```
run with ```docker run -v ./:/app --rm jamf-api```

## .env requirements
you'll need to provide a .env file with the following variables
```
JAMF_CLIENT_ID=
JAMF_CLIENT_SECRET=
```