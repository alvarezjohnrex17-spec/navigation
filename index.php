<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>DEBESMSCAT Campus Navigator</title>

    <link rel="stylesheet" href="assets/style.css?v=3">
</head>

<body>

<div class="container">

    <header class="header">

        <div class="brand">

            <div class="brand-logo-wrapper">
                <img
                    src="NEW-LOGO_WHITE-BACKGROUND.png"
                    alt="DEBESMSCAT Official Logo"
                    class="brand-logo"
                >
            </div>

            <div class="brand-copy">
                <h1>DEBESMSCAT Campus Navigator</h1>

                <p>
                    Dr. Emilio B. Espinosa, Sr. Memorial State College - Cabitan
                </p>
            </div>

        </div>

        <div class="badge green">
            ✓ No Location Needed | Arrow Follows Road
        </div>

    </header>


    <main class="login-grid">

        <!-- STUDENT CARD -->
        <section class="card student-card">

            <div class="icon-box green">🎓</div>

            <h2>I'm a Student</h2>

            <p>No password needed</p>

            <ul class="check">
                <li>✓ No GPS required</li>
                <li>✓ Arrow follows road line (gray roads)</li>
                <li>✓ Auto log: name, course, year</li>
            </ul>

            <a href="student.php" class="btn btn-green">
               Student →
            </a>

        </section>


        <!-- ADMIN CARD -->
        <section class="card admin-card">

            <div class="icon-box dark">🛡️</div>

            <h2>I'm an Admin</h2>

            <p>for maintenance only</p>

            <ul class="check">
                <li>• Total visits and today's count</li>
                <li>• Name, Course, Year (1st–4th), Purpose</li>
                <li>• Charts and Export CSV</li>
            </ul>

            <a href="admin.php" class="btn btn-dark">
                Admin →
            </a>

        </section>

    </main>


    <footer class="footer">
        <p>
            College of Education • General Library • CCIT • College of Engineering • Agriculture • Industrial Technology
        </p>

        <p>
            © <?php echo date('Y'); ?>
            DEBESMSCAT Campus Navigator. All rights reserved.
        </p>
    </footer>

</div>

</body>
</html>
```
