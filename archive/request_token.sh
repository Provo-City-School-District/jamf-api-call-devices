#!/bin/bash
source .env


username=$USERNAME
password=$PASSWORD
url="https://provoschooldistrict.jamfcloud.com/"

# Variable declarations
bearerToken=""
tokenExpirationEpoch="0"

getBearerToken() {
    response=$(curl -s -u "$username":"$password" "$url"/api/v1/auth/token -X POST)
    echo $response
    bearerToken=$(echo "$response" | plutil -extract token raw -)
    tokenExpiration=$(echo "$response" | plutil -extract expires raw - | awk -F . '{print $1}')
    tokenExpirationEpoch=$(date -j -f "%Y-%m-%dT%T" -j -u -f "%Y-%m-%dT%H:%M:%S" "$tokenExpiration" +"%s")
}

checkTokenExpiration() {
    nowEpochUTC=$(date -u +"%s")
    if [[ $tokenExpirationEpoch -gt $nowEpochUTC ]]
    then
        echo "Token valid until the following epoch time: $tokenExpirationEpoch"
    else
        echo "No valid token available, getting new token"
        getBearerToken
    fi
}

invalidateToken() {
    responseCode=$(curl -w "%{http_code}" -H "Authorization: Bearer ${bearerToken}" "$url"/api/v1/auth/invalidate-token -X POST -s -o /dev/null)
    if [[ $responseCode == 204 ]]
    then
        echo "Token successfully invalidated"
        bearerToken=""
        tokenExpirationEpoch="0"
    elif [[ $responseCode == 401 ]]
    then
        echo "Token already invalid"
    else
        echo "An unknown error occurred while invalidating the token"
    fi
}

checkTokenExpiration
# curl -s -H "Authorization: Bearer ${bearerToken}" "$url"/api/v1/jamf-pro-version -X GET
# php jamf_api.php "$bearerToken"

echo "BEARER_TOKEN=$bearerToken" > token.env
echo "TOKEN_EXPIRATION=$tokenExpirationEpoch" >> token.env
# checkTokenExpiration
# invalidateToken
# checkTokenExpiration
# curl -s -H "Authorization: Bearer ${bearerToken}" "$url"/api/v1/jamf-pro-version -X GET