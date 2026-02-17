<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  <link rel="stylesheet" href="{{ asset('css/createaccount.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

  <div class="form-box">
    <h2>CREATE ACCOUNT</h2>

    <form id="createAccountForm" action="{{ route('register') }}" method="POST">
      @csrf
      <div class="section-title"><span>Personal Information</span></div>

      <div class="row">
        <div class="form-group">
          <label>First Name:</label>
          <input type="text" name="first_name" placeholder="Enter first name" required>
          <small class="error"></small>
        </div>

        <div class="form-group">
          <label>Last Name:</label>
          <input type="text" name="last_name" placeholder="Enter last name" required>
          <small class="error"></small>
        </div>

        <div class="form-group">
          <label>Contact No.:</label>
          <input type="text" name="contact_no" placeholder="Enter contact number" required>
          <small class="error"></small>
        </div>
      </div>

      <div class="role">
        <label>Role:</label>
        <div class="role-options">
          <label><input type="radio" name="role" value="Property Custodian"> Property Custodian</label>
          <label><input type="radio" name="role" value="Regular Employee"> Regular Employee</label>
        </div>
        <small class="error"></small>
      </div>

      <div class="section-title"><span>Login Details</span></div>

      <div class="form-group">
        <label>Email Address:</label>
        <input type="email" name="email" id="email" placeholder="Enter email address" required>
        <small class="error" id="emailError"></small>
      </div>

      <div class="form-group">
        <label>Create Password:</label>

        <div class="password-container">
          <input type="password" name="password" id="password" placeholder="Enter password" required>
          <span class="toggle-password" onclick="togglePassword('password', this)">👁</span>
        </div>

        <small id="password-strength" class="strength"></small>
        <small class="error"></small>
      </div>

      <div class="form-group">
        <label>Confirm Password:</label>

        <div class="password-container">
          <input type="password" name="password_confirmation" id="confirmPassword" placeholder="Confirm password"
            required>
          <span class="toggle-password" onclick="togglePassword('confirmPassword', this)">👁</span>
        </div>

        <small class="error"></small>
      </div>

      <div class="buttons">
        <a href="{{ route('login') }}" class="btn btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-signup">Sign Up</button>
      </div>
      <button type="submit" style="display:none"></button>
    </form>
  </div>

  <script src="{{ asset('js/createaccount.js') }}"></script>

</body>

</html>