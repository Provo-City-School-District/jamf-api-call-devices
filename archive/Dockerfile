FROM php:8.2-cli

# Set the working directory
WORKDIR /usr/src/app

# Install required packages
RUN apt-get update && apt-get install -y \
    curl \
    jq \
    wget

# COPY ckroot.crt /usr/local/share/ca-certificates/ckroot.crt
RUN wget -P /usr/local/share/ca-certificates/ "https://ckr01.provo.edu/ckroot/ckroot.crt"
RUN chmod 644 /usr/local/share/ca-certificates/ckroot.crt && update-ca-certificates


# Copy the Bash script and PHP script to the Docker image
COPY request_token.sh /usr/src/app/


# Run the Bash script to obtain the bearer token
RUN chmod +x request_token.sh

# Run the Bash script to obtain the bearer token and store it in a file
RUN /usr/src/app/request_token.sh > token.txt


# Install Guzzle HTTP client using Composer
RUN apt-get install -y unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer require guzzlehttp/guzzle

# Copy the required PHP files
COPY jamf_api.php /usr/src/app/

# Run the PHP script
CMD [ "php", "jamf_api.php" ]