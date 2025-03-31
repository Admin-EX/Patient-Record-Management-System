<div class="sidebar">
    <div class="logo">Dalayap Health Center</div>
    <a href="Home_index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Home_index.php' ? 'active' : ''; ?>">
        <img src="icons/home.png">Home
        <?php if (basename($_SERVER['PHP_SELF']) == 'Home_index.php') echo '<img src="icons/arrowr.png" style="float: right;">'; ?>
    </a>
    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <img src="icons/user.png">Add Patient
        <?php if (basename($_SERVER['PHP_SELF']) == 'index.php') echo '<img src="icons/arrowr.png" style="float: right;">'; ?>
    </a>
    <a href="Patient_list.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Patient_list.php' ? 'active' : ''; ?>">
        <img src="icons/people.png">List of Patients
        <?php if (basename($_SERVER['PHP_SELF']) == 'Patient_list.php') echo '<img src="icons/arrowr.png" style="float: right;">'; ?>
    </a>
    <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
        <img src="icons/calendar.png">Appointment
        <?php if (basename($_SERVER['PHP_SELF']) == 'appointments.php') echo '<img src="icons/arrowr.png" style="float: right;">'; ?>
    </a>
    <a href="Med.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Med.php' ? 'active' : ''; ?>">
        <img src="icons/med.png">Medicine Inventory
        <?php if (basename($_SERVER['PHP_SELF']) == 'Med.php') echo '<img src="icons/arrowr.png" style="float: right;">'; ?>
    </a>
    <form class="logout-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <input type="submit" name="logout" value="Logout">
        <p style="text-align: center; color: white; margin-top: 5px; font-size:8px;">Made by Syntax Squabbles</p>
    </form>
</div>
