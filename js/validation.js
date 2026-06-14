function validateForm() {

    let fullName = document.getElementById("full_name").value;
    let email = document.getElementById("email").value;
    let regNo = document.getElementById("reg_no").value;
    let faculty = document.getElementById("faculty").value;
    let password = document.getElementById("password").value;

    if(fullName == "" ||
       email == "" ||
       regNo == "" ||
       faculty == "" ||
       password == "") {

        alert("All fields are required!");
        return false;
    }

    if(password.length < 6){
        alert("Password must be at least 6 characters.");
        return false;
    }

    return true;
}