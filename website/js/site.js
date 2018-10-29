function checkLength10(elem){
	if (elem.value.length > 10){
		elem.value = elem.value.substring(0,10);
	}
}

function localStore() {
		let checkBox = document.getElementById("storage").checked;
    if(checkBox) {
			let item = document.getElementById("username").value;
			localStorage.setItem('username', item);
			return true;
		}
		return true;
}
