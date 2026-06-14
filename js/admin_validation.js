function validateAdmin() {

    let name = document.getElementById("full_name").value;
    let email = document.getElementById("email").value;
    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;

    if(name === "" || email === "" || username === "" || password === "") {
        alert("All fields are required.");
        return false;
    }

    if(password.length < 6){
        alert("Password must be at least 6 characters.");
        return false;
    }

    return true;
}