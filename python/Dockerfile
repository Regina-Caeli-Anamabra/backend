# Dockerfile Example
FROM python:3.9-slim

# Set the working directory inside the container
WORKDIR /app

# Copy the requirements file and install dependencies
COPY requirements.txt /app/
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of your application
COPY . /app/

# Ensure the application starts when the container starts
ENTRYPOINT ["python", "sync_database.py"]
