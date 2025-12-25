CREATE TABLE IF NOT EXISTS tasks (
                                     id SERIAL PRIMARY KEY,
                                     status VARCHAR(20) DEFAULT 'PENDING', -- PENDING, PROCESSING, DONE, ERROR
    input_file TEXT,
    result_xml TEXT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE IF NOT EXISTS family_members (
                                              id SERIAL PRIMARY KEY,
                                              last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    birth_date DATE NOT NULL,
    relation VARCHAR(50),
    is_applicant BOOLEAN DEFAULT FALSE,
    task_id INT REFERENCES tasks(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_member UNIQUE (last_name, first_name, middle_name, birth_date)
    );

CREATE TABLE IF NOT EXISTS real_estate (
                                           id SERIAL PRIMARY KEY,
                                           member_id INT REFERENCES family_members(id) ON DELETE CASCADE,
    type VARCHAR(100),
    address TEXT,
    ownership VARCHAR(50)
    );