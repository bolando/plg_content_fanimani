var myCustomFields_Rating_active = false;
var switchCustomFields_Rating = {inputs:null, copy:null, copy_labels:null, classActive: ["btn active btn-success", "btn active btn-danger"], classNormal:"btn",status:null};
var mySave_Rating = {save:null,saveclose:null,savenew:null,savecopy:null};

function addMyCustomFields_Rating(){
	if( !myCustomFields_Rating_active ) return;
	var ID_fieldset = "attrib-fanimani";
	var ID_fieldset_Msg = "jform_attribs_fanimani_msg";
	switchCustomFields_Rating.inputs = document.getElementById(ID_fieldset).children[0].querySelectorAll("input");
	var nodes = document.getElementById(ID_fieldset).children[0].cloneNode(true);
	switchCustomFields_Rating.copy = nodes.querySelectorAll("input");
	switchCustomFields_Rating.copy_labels = nodes.querySelectorAll(".controls label");
	//TODO: hide menu
	document.querySelector('a[href="#'+ID_fieldset+'"]').style.display = "none";
	
	if( document.URL.indexOf('option=com_content') > 0 && document.URL.indexOf('view=article') > 0 && document.URL.indexOf('layout=edit') > 0){
		switchCustomFields_Rating.copy.forEach(function(el){
			el.onclick = function(){
				var i = (switchCustomFields_Rating.copy[0]==el)?0:1;
				switchCustomFields_Rating.copy_labels[i].className = switchCustomFields_Rating.classActive[i];
				switchCustomFields_Rating.copy_labels[(i+1)%2].className = switchCustomFields_Rating.classNormal;
				switchCustomFields_Rating.inputs[i].checked = true;
				switchCustomFields_Rating.inputs[(i+1)%2].checked = false;
			}
		});
		var msg = document.createElement("P");
			msg.innerHTML = document.getElementById(ID_fieldset_Msg).value;
			
		nodes.append(msg);
		document.getElementById("general").querySelector(".span3 fieldset").append(nodes);
	//console.log(switchCustomFields_Rating);
	}
}

function setMyCustomFields_Rating(on){
	switchCustomFields_Rating.status = on;
}


	window.onload = function(){ 
		myCustomFields_Rating_active = true;
		addMyCustomFields_Rating();
	}
