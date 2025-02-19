// Function to open the course edit modal
function editCourse(courseId) {
    fetch(`fetch_course_edit.php?course_id=${courseId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById("courseDetails").innerHTML = data;
            document.getElementById("courseModal").style.display = "block";
        });
}

// Function to close the modal
function closeModal() {
    document.getElementById("courseModal").style.display = "none";
}

// Event listener for clicking outside the modal to close it
window.onclick = function(event) {
    if (event.target == document.getElementById("courseModal")) {
        closeModal();
    }
}
