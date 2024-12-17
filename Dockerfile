# Use an official Python runtime as a parent image
FROM php:8.4-cli

# Install curl and jq
RUN apt-get update && \
    apt-get install -y curl jq

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Set the working directory in the container to /app
WORKDIR /app

# Copy the current directory contents into the container at /app
ADD . /app

# Run the script when the container launches
CMD ["bash", "request_script.sh"]