import os
import mysql.connector
import time

db_config = {
    'host': os.environ.get("DB_HOST"),
    'user': os.environ.get("MYSQL_USER"),
    'password': os.environ.get("MYSQL_PASSWORD"),
    'database': os.environ.get("MYSQL_DATABASE"),
}


def move_data():
    host = os.environ.get("DB_HOST")
    print(f"host is {host}")
    """Move data from 'patientdontuse' to 'users' and update 'patient'"""
    try:
        # Connect to the database
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)

        # Select data from 'patientdontuse' table
        select_query = "SELECT * FROM patientdontuse WHERE moved = 0"
        cursor.execute(select_query)

        rows = cursor.fetchall()
        if not rows:
            print("No data to move.")
            return

        for row in rows:
            # Extract fields
            firstName = row.get('firstName')
            middleName = row.get('middleName')
            lastName = row.get('lastName')
            dateOfBirth = row.get('dateOfBirth')
            gender = row.get('gender')
            marital_status = row.get('marital_status')
            nationality = row.get('nationality')
            state_of_origin = row.get('state_of_origin')
            lga = row.get('lga')
            ethnic = row.get('ethnic')
            phone_no = row.get('phone_no')
            email = row.get('email')
            state_of_residence = row.get('state_of_residence')
            address = row.get('address')
            next_of_kin = row.get('next_of_kin')
            next_of_kin_relationship = row.get('next_of_kin_relationship')
            next_of_kin_phoneno = row.get('next_of_kin_phoneno')
            next_of_kin_address = row.get('next_of_kin_address')
            town = row.get('town')
            permanent_address = row.get('permanent_address')
            patient_id = row.get('patient_id')

            # You didn't define 'religion' in the source table — using default/None
            religion = row.get('religion', None)

            # Insert data into 'users' table
            insert_users_query = """
                INSERT INTO offline_online_patients_sync
                (reg_id, firstName, lastName, phone, gender, marital_status, religion, nationality,
                 next_of_kin, next_of_kin_phone, nature_of_relationship, date_of_birth,
                 state_of_residence, address_of_residence, address_of_next_of_kin)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            cursor.execute(insert_users_query, (
                patient_id, firstName, lastName, phone_no, gender, marital_status, religion, nationality,
                next_of_kin, next_of_kin_phoneno, next_of_kin_relationship, dateOfBirth,
                state_of_residence, permanent_address, next_of_kin_address, hashed_password
            ))


            # Update 'patientdontuse' table to mark as moved
            update_patient_query = "UPDATE patientdontuse SET moved = %s WHERE patient_id = %s"
            cursor.execute(update_patient_query, (1, patient_id))

            # Commit transaction for each row
            connection.commit()
            print(f"✅ Moved data: {phone_no} | Patient ID: {patient_id} | User ID: {user_id}")

    except mysql.connector.Error as err:
        print(f"❌ Database Error: {err}")

    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()


def wait_for_db():
    host = os.environ.get("DB_HOST")
    print(f"host is {host}")
    """Wait for database connection to become available"""
    while True:
        try:
            conn = mysql.connector.connect(**db_config)
            conn.close()
            print("✅ Database is ready!")
            break
        except mysql.connector.Error as err:
            print(f"⏳ Waiting for DB... Error: {err}")
            time.sleep(2)


if __name__ == "__main__":
    wait_for_db()
    while True:
        move_data()
        time.sleep(10)  # Wait for 10 seconds before running again
