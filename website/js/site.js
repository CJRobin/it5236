function checkLength10(elem){
	if (elem.value.length > 10){
		elem.value = elem.value.substring(0,10);
	}
}

function localStorage() {
		alert("hi");
		let checkBox = document.getElementById("storage").checked;
		alert(checkBox);
    if(checkBox) {
			alert("ischecked");
			localStorage.setItem("user", document.getElementById("username").value);
			return true;
		}
		return false;
}
