# Use an official Python runtime as a parent image
FROM python:3.7-slim

# Install curl and jq
RUN apt-get update && \
    apt-get install -y curl jq

# Set the working directory in the container to /app
WORKDIR /app

# Copy the current directory contents into the container at /app
ADD . /app

# Run the script when the container launches
CMD ["bash", "request_script.sh"]