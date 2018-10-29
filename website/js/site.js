function checkLength10(elem){
	if (elem.value.length > 10){
		elem.value = elem.value.substring(0,10);
	}
}

function localStorage() {
		alert(document.getElementById("username").value);
		let checkBox = document.getElementById("storage").checked;
		alert(checkBox);
    if(checkBox) {
			let item = document.getElementById("username").value
			localStorage.setItem("user", item);
			return true;
		}
		return false;
}
