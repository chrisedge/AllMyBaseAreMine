// JavaScript Document
function validateDate(formName) {
	var re_date = /^\s*(\d{2,4})\-(\d{1,2})\-(\d{1,2})\s*$/;
	var startd = formName.elements["startDate"].value;
	var endd = formName.elements["endDate"].value;
	if (!re_date.exec(startd)) {
        alert ("Invalid start date.\nAccepted format is yyyy-mm-dd.");
        return false;
    }
	if (!re_date.exec(endd)) {
		alert ("Invalid end date.\nAccepted format is yyyy-mm-dd.");
		return false;
    }
}




function checkform() {
      var re_date = /^\s*(\d{2,4})\-(\d{1,2})\-(\d{1,2})\s*$/;
      var startd = setimeform.elements["startDate"].value;
      var endd = setimeform.elements["endDate"].value; 
      if (!re_date.exec(startd)) {
        alert ("Invalid start date.\nAccepted format is yyyy-mm-dd.");
        return false;
      }
      else {
        if (!re_date.exec(endd)) {
          alert ("Invalid end date.\nAccepted format is yyyy-mm-dd.");
          return false;
        }
        else {
          return true;
        }  
      }
    }  

    function setUserValue(user) {
      setimeform.elements["mgr"].value = user;
    }

    function submitFormByProxy(user) {
      setimeform.elements["mgr"].value = user;
      if (!checkform()) {
        return false;
      } else {
        setimeform.submit();
      }
    }

    function submitform() {
      download_setime.elements["startDate"].value = setimeform.elements["startDate"].value; 
      download_setime.elements["endDate"].value = setimeform.elements["endDate"].value; 
      download_setime.elements["mgr"].value = setimeform.elements["mgr"].value; 
      return checkform();
    }
	
function loadXMLDoc(dateLabel,numActivities,hoursLeft) {
	var xmlhttp;
	var numActivities = (parseInt(numActivities) + 1);
	var where = 'container' + dateLabel;
	var getString = 'addTime.php?dateLabel=' + dateLabel + '&hoursLeft=' + hoursLeft;
	if (window.XMLHttpRequest)
	  {// code for IE7+, Firefox, Chrome, Opera, Safari
	  xmlhttp=new XMLHttpRequest();
	  }
	else
	  {// code for IE6, IE5
	  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	xmlhttp.onreadystatechange=function()
	  {
	  if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		// document.getElementById("timeContainer").innerHTML+=xmlhttp.responseText;
		// document.getElementById(where).innerHTML+=dateLabel + ' ' + numActivities + ' ' + getString;
		document.getElementById(where).innerHTML+=xmlhttp.responseText;
		}
	  }
	xmlhttp.open("GET",getString,true);
	xmlhttp.send();
}

function loadXMLDocNG(dateLabel,numActivities,hoursLeft) {
	var xmlhttp;
	var numActivities = (parseInt(numActivities) + 1);
	var where = 'entryTable' + dateLabel;
	var getString = 'addTimeNG.php?dateLabel=' + dateLabel + '&hoursLeft=' + hoursLeft;
	if (window.XMLHttpRequest)
	  {// code for IE7+, Firefox, Chrome, Opera, Safari
	  xmlhttp=new XMLHttpRequest();
	  }
	else
	  {// code for IE6, IE5
	  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	xmlhttp.onreadystatechange=function()
	  {
	  if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		// document.getElementById("timeContainer").innerHTML+=xmlhttp.responseText;
		// document.getElementById(where).innerHTML+=dateLabel + ' ' + numActivities + ' ' + getString;
		document.getElementById(where).innerHTML+=xmlhttp.responseText;
		}
	  }
	xmlhttp.open("GET",getString,true);
	xmlhttp.send();
}


function resetDefaultValues(what) {
    for (var i=0, j=what.elements.length; i<j; i++) {
        myType = what.elements[i].type;
        //if (myType == 'checkbox' || myType == 'radio')
        //    what.elements[i].checked = what.elements[i].defaultChecked;
        //if (myType == 'hidden' || myType == 'password' || myType == 'text' || myType == 'textarea')
        //    what.elements[i].value = what.elements[i].defaultValue;
        if (myType == 'select-one' || myType == 'select-multiple')
            for (var k=0, l=what.elements[i].options.length; k<l; k++)
                what.elements[i].options[k].selected = what.elements[i].options[k].defaultSelected;
    }
}

function updateHours(x,initialHoursLeft,dateLabel) { // Couldn't get this to work right. Maybe come back to it.
// It was called like this:
// <select name="<?php echo $label; ?>_activity<?php echo $numActivities; ?>_hours" 
// id="<?php echo $label; ?>_activity<?php echo $numActivities; ?>_hours" 
// onchange="updateHours(this.id,<?php echo "'".$hoursLeft."','".$label."'"; ?>)">
	var where = dateLabel + '_hoursLeft';
	var y = parseFloat(document.getElementById(x).value); // Value in the select that has changed.
	var z = parseFloat(initialHoursLeft);
	var hoursLeft = parseFloat(document.getElementById(where).value); // Stored value that will be updated.
	// The first time this script is called initialHoursLeft(z) and hoursLeft will be the same.
	// Any subsequent calls to this script will have modified hoursLeft, so we will need to add
	// initialHoursLeft back to hoursLeft to account for this change.
	if ( z != hoursLeft ) {
		hoursLeft = hoursLeft + z;
	}
	// Subtract the number of hours they selected from hoursLeft, if it's less than 0, alert.
	if ( (hoursLeft - y) < 0 ) {
		alert ("Maximum number of hours per day for " + dateLabel + " has been exceeded.");
		//resetDefaultValues(document.forms["addTime"]);
		//document.getElementById(where).value = initialHours;
		//history.go(0);
		//window.location.reload(true);
		window.location = window.location.href;
		return false;
	} else {
		var newHours = (hoursLeft - y);
		document.getElementById(where).value=newHours;
		return true;
	}
}

function updateEditedHours(x,initialHours,initialHoursLeft,dateLabel) {
	// This one had to be branched off. In edit_time.php, when the hours are changed, they have to
	// reflect hoursLeft based on the existing hours being added back into the total of what is left.
	// For example: if a user has entered 8 hours for one day already, they only have 4 hours left to
	// add (due to the 12 hour limit). But, when a user edits that entry and changes those hours from
	// 8 to something else, those 8 hours have to first be added on to hoursLeft before the new
	// value of hoursLeft can be calculated.
	var where = dateLabel + '_hoursLeft';
	var y = parseFloat(document.getElementById(x).value);
	alert("y is: " + y);
	var z = parseFloat(document.getElementById(where).value);
	alert("z is: " + z);
	var hoursLeft = y+z;
	// Subtract the number of hours they selected from hoursLeft, if it's less than 0, alert.
	if ( (hoursLeft - y) < 0 ) {
		alert ("Maximum number of hours per day for " + dateLabel + " has been exceeded.");
		//resetDefaultValues(document.forms["addTime"]);
		//document.getElementById(where).value = initialHours;
		//history.go(0);
		//window.location.reload(true);
		window.location = window.location.href;
		return false;
	} else {
		var newHours = (hoursLeft - y);
		document.getElementById(where).value=newHours;
		return true;
	}
}

function setLocal(x,value) {
	new_value = parseInt(value);
	new_value = 'local_' + new_value;
	document.getElementById(x).value=new_value;
	// alert("x: " + x + " new_value: " + new_value);
}

function alertAndReturn(Msg,Url) {
	alert (Msg);
	window.location = Url;
	return true;
}

function validateLocalCode(formName) {
	var fieldID='description'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue=="") {
		alert("A value must be entered for Description.");
		document.forms[formName][fieldID].focus();
		return false;
	}
	
	var fieldID='comments'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue=="") {
		alert("A value must be entered for Comments.");
		document.forms[formName][fieldID].focus();
		return false;
	}
	
	var fieldID='global_id'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue=="") {
		alert("A value must be entered for Global Code.");
		document.forms[formName][fieldID].focus();
		return false;
	}
	
	var fieldID='delete'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue == "Delete Code" ) {
		confirm("Delete this local code?");
	}
}

function validateDelete(formName) {
	var fieldID='submit'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( confirm("Delete this local code?" + '\r\n' + "It is better to simply mark the code as not active."
	+ '\r\n' + "Do you still want to delete this local code?") ) {
		return true;
	} else { return false; }

}

function validateDeleteTimeNG(formName) {
	var fieldID = 'delete'
	
	if ( document.forms[formName][fieldID].checked ) {
		if ( confirm("Are you sure you want to delete this time entry?") ) {
			document.forms[formName][fieldID].value='true'
			document.forms[formName].submit();
			return true;
		} else { return false; }
	}
}


function validateDeletePos(formName) {
	// Set positionMod.delete.value to 'true' if they really want to delete it.
	var fieldID='position'
	var deleteID='delete_id'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue =="" ) {
		alert("A position must be selected.");
		return false;
	}
	if ( fieldIDValue=="1" ) {
		alert("You can not delete the lowest level position.");
		return false;
	}
	if ( confirm("Delete this position?" + '\r\n' + "Users with this position will have their position set to NULL in the database." + '\r\n' +  "Are you sure?") ) {
		document.forms[formName][deleteID].value='true'
		document.forms[formName].submit();
		return true;
	} else { return false; }

}

function validateDeleteGlobal(formName) {
	// Set positionMod.delete.value to 'true' if they really want to delete it.
	var fieldID='code'
	var deleteID='delete_id'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue =="" ) {
		alert("A global code must be selected.");
		return false;
	}
	if ( confirm("Delete this global code?" + '\r\n' + "All time entries related to this code will also be deleted!" + '\r\n' +  "You can always just make the code inactive." + '\r\n' + "Are you sure?") ) {
		document.forms[formName][deleteID].value='true'
		document.forms[formName].submit();
		return true;
	} else { return false; }

}

function validateDeleteCat(formName) {
	// Set positionMod.delete.value to 'true' if they really want to delete it.
	var fieldID='category'
	var deleteID='delete_id'
	var fieldIDValue=document.forms[formName][fieldID].value
	if ( fieldIDValue==null || fieldIDValue =="" ) {
		alert("A category must be selected.");
		return false;
	}
	if ( confirm("Delete this category?" + '\r\n' + "All global and local activity codes associated with this category" + '\r\n' + "will also be deleted!" + '\r\n' +  "All time entries associated with this category will also be deleted!" + '\r\n' + "You can always just make the category inactive." + '\r\n' + "Are you sure?") ) {
		document.forms[formName][deleteID].value='true'
		document.forms[formName].submit();
		return true;
	} else { return false; }

}

function validateDeleteTime(formName) {
	if ( confirm("Delete this entry?" + '\r\n' + "Are you sure want to delete this time entry?") ) {
		return true;
	} else { return false; }

}

function loadDirects(currentID) {
	var xmlhttp;
	// var numActivities = (parseInt(numActivities) + 1);
	var currentID = (parseInt(currentID));
	// var previousID = (parseInt(previousID));
	var getString = 'getDirects.php?current=' + currentID;
	// alert("Get String " + getString);
	if (window.XMLHttpRequest)
	  {// code for IE7+, Firefox, Chrome, Opera, Safari
	  xmlhttp=new XMLHttpRequest();
	  }
	else
	  {// code for IE6, IE5
	  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	xmlhttp.onreadystatechange=function()
	  {
	  if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		// document.getElementById("timeContainer").innerHTML+=xmlhttp.responseText;
		// document.getElementById(where).innerHTML+=dateLabel + ' ' + numActivities + ' ' + getString;
		document.getElementById("directReports").innerHTML=xmlhttp.responseText;
		}
	  }
	xmlhttp.open("GET",getString,true);
	xmlhttp.send();
}