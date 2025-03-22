CREATE TABLE alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    middle_name VARCHAR(255),
    last_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL, -- 'Course' or 'Volunteer'
    details TEXT NOT NULL,
    status VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id)
);
