function resetPassword() 
{		
	var l_url = '{UIHOST}/xul_controller.php?module=users&action=ResetPassword&access=back';
	window.open(l_url, 'resetPsswd', 'width=525, height=190, modal=yes, dialog=yes, titlebar=no, alwaysRaised=yes, close=yes, menubar=no, toolbar=no, location=no, resizable=yes, scrollbars=yes, status=no"');
}