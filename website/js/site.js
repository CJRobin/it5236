function checkLength10(elem){
	if (elem.value.length > 10){
		elem.value = elem.value.substring(0,10);
	}
}

function localStorage() {
		alert("hi");
    if(document.getElementById("storage").checked) {
			localStorage.setItem("user", document.getElementById("username").value);
			alert("ischecked");
			return true;
		}
		return false;
}
