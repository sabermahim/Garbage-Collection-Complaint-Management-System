
CREATE TABLE complaint_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT,
    status VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (complaint_id) REFERENCES complaints(id)
);
