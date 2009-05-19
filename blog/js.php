<?php
$js = <<<EOF
//function to clear the list of context associations
function emptyAssocList() {
  var modassoc = document.getElementById('id_modassoc');
  while(modassoc.length > 0) {
    modassoc.remove(0);
  }
}

//function for adding an element to the list of context associations
function addModAssoc(name, id) {
  var modassoc = document.getElementById('id_modassoc');
  newoption = document.createElement('option');
  newoption.text = name;
  newoption.value = id;
  try {
    modassoc.add(newoption, null);  //standard, broken in IE
  } catch(ex) {
  modassoc.add(newoption);
  }
}
EOF;
