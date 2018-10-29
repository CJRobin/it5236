function checkLength10(elem){
	if (elem.value.length > 10){
		elem.value = elem.value.substring(0,10);
	}
}

function localStorage() {
    if(document.getElementById("storage").checked) {
			localStorage.setItem("user", document.getElementById("username").value);
			return true;
		}
		return false;
}
