<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLS · Patient Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <h1>Patient Registration</h1>
    <p>Full name, phone, email, insurance, etc.</p>

    <form>
        <div>
            <label for="fullname">Full name</label>
            <input type="text" id="fullname" placeholder="e.g. Maria Santos">
        </div>
        <div>
            <label for="phone">Phone</label>
            <input type="tel" id="phone" placeholder="+63 912 345 6789">
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" placeholder="maria@example.com">
        </div>
        <div>
            <label for="hmo">HMO / Insurance</label>
            <select id="hmo">
                <option>Maxicare</option>
                <option>Intellicare</option>
                <option>MediCard</option>
                <option>Other</option>
            </select>
        </div>
        <div>
            <label for="specialty">Preferred specialty</label>
            <select id="specialty">
                <option>Internal Medicine</option>
                <option>Orthopedics</option>
                <option>Dermatology</option>
                <option>Gastroenterology</option>
                <option>Neurology</option>
                <option>Reproductive Health</option>
            </select>
        </div>
        <div>
            <button type="submit">Register & continue</button>
        </div>
    </form>
</body>
</html>
