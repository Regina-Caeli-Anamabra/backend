import os
import mysql.connector
import time
import bcrypt

# Database configuration using environment variables
db_config = {
    'host': os.environ.get("DB_HOST"),
    'user': os.environ.get("MYSQL_USER"),
    'password': os.environ.get("MYSQL_PASSWORD"),
    'database': os.environ.get("MYSQL_DATABASE"),
}


# Function to move data from 'patient' table to 'users' table and update 'patient'def move_data():
def move_data():
    try:
        # Connect to the database
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)

        # Select data from 'patient' table
        select_query = "SELECT phone_no, patient_id FROM patient WHERE moved = 0"
        cursor.execute(select_query)

        # Fetch the data (all results)
        rows = cursor.fetchall()
        if rows:
            row = rows[0]
            phone_no = row['phone_no']
            patient_id = row['patient_id']

            # Hash the password for user creation (replace with actual password)
            password = "12345"
            hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())

            # Insert data into 'users' table
            insert_users_query = "INSERT INTO users (phone, reg_id, verified, password) VALUES (%s, %s, 1, %s)"
            cursor.execute(insert_users_query, (phone_no, patient_id, hashed_password))

            # Get the ID of the newly created user (use lastrowid to get the inserted user's ID)
            user_id = cursor.lastrowid
            print(f"Created user with ID: {user_id}")

            # Update 'patient' table with the user_id from the 'users' table
            update_patient_query = "UPDATE patient SET user_id = %s, moved = %s WHERE patient_id = %s"
            cursor.execute(update_patient_query, (user_id, 1, patient_id))

            # Commit the transactions
            connection.commit()
            print(f"Moved data: {phone_no}, Patient ID: {patient_id}, User ID: {user_id}")

        else:
            print("No data to move.")

    except mysql.connector.Error as err:
        print(f"Error: {err}")

    finally:
        # Close the database connection
        if connection.is_connected():
            cursor.close()
            connection.close()


def wait_for_db():
    while True:
        try:
            conn = mysql.connector.connect(**db_config)
            conn.close()  # Close the connection immediately after successful check
            print("Database is ready!")
            break
        except mysql.connector.Error as err:
            print(f"Waiting for DB... Error: {err}")
            time.sleep(2)


# Run the script every 10 seconds
if __name__ == "__main__":
    wait_for_db()
    while True:
        move_data()
        time.sleep(2)  # Wait for 10 seconds before running again
