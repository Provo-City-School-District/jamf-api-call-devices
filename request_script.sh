#!/bin/bash
source .env

getBearerToken() {
    response=$(curl --location --request POST 'https://provoschooldistrict.jamfcloud.com/api/oauth/token' \
    --header 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "client_id=${JAMF_CLIENT_ID}" \
    --data-urlencode 'grant_type=client_credentials' \
    --data-urlencode "client_secret=${JAMF_CLIENT_SECRET}")

    # echo $response
    echo $response > response.json
}

getComputerInventory() {
    access_token=$(jq -r '.access_token' response.json)
    echo "Access Token: ${access_token}"  # Print the access token
    inventory=$(curl -X GET 'https://provoschooldistrict.jamfcloud.com/api/v1/computers-inventory?section=GENERAL&section=HARDWARE&section=OPERATING_SYSTEM&page=0&page-size=1000000&sort=general.name%3Aasc' \
    --header "Authorization: Bearer ${access_token}")

    # echo $inventory
    echo $inventory > inventory.json
}

getBearerToken
getComputerInventory
php push-to-vault.php