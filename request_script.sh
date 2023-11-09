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
    page=0
    while true; do
        inventory=$(curl -X GET "https://provoschooldistrict.jamfcloud.com/api/v1/computers-inventory?section=GENERAL&section=HARDWARE&section=OPERATING_SYSTEM&page=${page}&page-size=1000&sort=general.name%3Aasc" \
        --header "Authorization: Bearer ${access_token}")
        if [ "$(jq -r '.results | length' <<< "$inventory")" -eq 0 ]; then
            break
        fi
        echo $inventory > computer_${page}.json
        page=$((page+1))
    done
}

getMobileInventory(){
    access_token=$(jq -r '.access_token' response.json)
    page=0
    while true; do
        mobile_inventory=$(curl -X GET "https://provoschooldistrict.jamfcloud.com/api/v2/mobile-devices/detail?section=GENERAL&section=HARDWARE&page=${page}&page-size=1000&sort=displayName%3Aasc" \
        --header "Authorization: Bearer ${access_token}")
        if [ "$(jq -r '.results | length' <<< "$mobile_inventory")" -eq 0 ]; then
            break
        fi
        echo $mobile_inventory > mobile_${page}.json
        page=$((page+1))
    done
}

getBearerToken
getComputerInventory
getMobileInventory
php push-to-vault.php