# Family Data Processing Service

A backend service implemented in native PHP 8.2 and PostgreSQL to process XML data regarding family members. The service parses family data, stores it in a database with deduplication, retrieves real estate information from an internal mock service, and generates a consolidated XML report.

It supports both Synchronous (direct response) and Asynchronous (task queue) processing patterns.

## Tech Stack
- **Language**: PHP 8.2 (Native, no frameworks)
- **Database**: PostgreSQL 15
- **Server**: Nginx (Alpine)
- **Containerization**: Docker & Docker Compose

## Key Features
- **XML Parsing & Generation**: Robust handling of input/output XML formats.
- **Data Integrity**: Transactional database operations to ensure data consistency.
- **Deduplication**: Prevents duplicate family members using unique database constraints (Upsert strategy).
- **Asynchronous Processing**: Custom implementation of a task queue for long-running operations.
- **Dockerized**: Fully containerized environment for easy deployment.

## Installation & Setup

### Prerequisites
- Docker
- Docker Compose

### Steps to Run

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ErlanKurmanov/xml-processor.git
   cd my-family-service
   ```

2. **Build and Start Containers:** This command will build the PHP image, start Nginx and PostgreSQL, and automatically apply the database schema via init.sql.
   ```bash
   docker compose up -d --build
   ```

3. **Run the Background Worker (For Async Processing):** To process tasks sent to the upload endpoint, you need to run the worker script inside the container:
   ```bash
   docker compose exec app php worker.php
   ```
   (Keep this terminal window open to see the worker logs)

The API will be available at `http://localhost:8080`.

## API Documentation

### 1. Synchronous Processing
Process the XML immediately and return the result.

**Endpoint:** `POST /api/family/process`

**Content-Type:** `application/xml`

**Body:**
```xml
<Family>
   <Member>
      <LastName>Иванов</LastName>
      <FirstName>Иван</FirstName>
      <MiddleName>Иванович</MiddleName>
      <BirthDate>1985-03-21</BirthDate>
      <Relation>Отец</Relation>
   </Member>
</Family>
```

**Response:** Returns the generated FamilyRealEstateResult XML.

### 2. Asynchronous Processing
Upload the file to a queue. The worker processes it in the background.

#### A. Upload Task
**Endpoint:** `POST /api/family/upload`

**Content-Type:** `application/xml`

**Body:** (Same XML as above)

**Response:**
```json
{
    "taskId": 1,
    "status": "PENDING"
}
```

#### B. Get Task Result
Check the status or get the final XML.

**Endpoint:** `GET /api/family/{taskId}/result`

**Response (If Processing):** `{"status": "PROCESSING"}`

**Response (If Done):** Returns the final XML content.

### 3. Family Member Data
Retrieve stored data from the database.

**Endpoint:** `GET /api/family/members/{id}`

**Response:** JSON object of the family member.

**Endpoint:** `GET /api/family/members/{id}/real-estate`

**Response:** JSON array of real estate objects owned by the member.

## Database Schema
- **family_members**: Stores personal info. Includes a UNIQUE constraint on (Last Name, First Name, Middle Name, Birth Date) to prevent duplicates.
- **real_estate**: Stores property information linked to family members.
- **tasks**: Stores asynchronous job states (PENDING, PROCESSING, DONE, ERROR) and XML payloads.