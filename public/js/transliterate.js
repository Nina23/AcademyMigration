function transliterate(word){
    console.log(word);
    var answer = ""
      , a = {};

   a["А"]="A";
   a["Б"]="B";
   a["В"]="V";
   a["Г"]="G";
   a["Д"]="D";
   a["Ђ"]="Đ";
   a["Е"]="E";
   a["Ж"]="Ž";
   a["З"]="Z";
   a["И"]="I";
   a["Ј"]="J";
   a["К"]="K";
   a["Л"]="L";
   a["Љ"]="LJ";
   a["М"]="M";
   a["Н"]="N";
   a["Њ"]="NJ";
   a["О"]="O";
   a["П"]="P";
   a["Р"]="R";
   a["С"]="S";
   a["Т"]="T";
   a["Ћ"]="Ć";
   a["У"]="U";
   a["Ф"]="F";
   a["Х"]="H";
   a["Ц"]="C";
   a["Ч"]="Č";
   a["Џ"]="DŽ";
   a["Ш"]="Š";

   a["а"]="a";
   a["б"]="b";
   a["в"]="v";
   a["г"]="g";
   a["д"]="d";
   a["ђ"]="đ";
   a["е"]="e";
   a["ж"]="ž";
   a["з"]="z";
   a["и"]="i";
   a["ј"]="j";
   a["к"]="k";
   a["л"]="l";
   a["љ"]="lj";
   a["м"]="m";
   a["н"]="n";
   a["њ"]="nj";
   a["о"]="o";
   a["п"]="n";
   a["р"]="p";
   a["с"]="c";
   a["т"]="t";
   a["ћ"]="ć";
   a["у"]="u";
   a["ф"]="f";
   a["х"]="h";
   a["ц"]="c";
   a["ч"]="č";
   a["џ"]="dž";
   a["ш"]="š";
   

   for (i in word){
     if (word.hasOwnProperty(i)) {
       if (a[word[i]] === undefined){
         answer += word[i];
       } else {
         answer += a[word[i]];
       }
     }
   }
   return answer;
}


function keyup(idElement) {
  //setting your input text to the global Javascript Variable for every key press
 if($(event.target)[0].id==idElement+'[sr]'){
  var inputTextValue;
  inputTextValue =document.getElementById(idElement+'[sr]').value;
  document.getElementById(idElement+'[sr-latn]').value=transliterate(inputTextValue);
 }
 }

 //CKEDITOR.on( 'instanceCreated', function ( event, data ) {
   //     CKEDITOR.instances["body[sr-latn]"].insertText("mini me");
     //   var editor = this;
      //  var text = editor.getData();
    // var originalTextarea = editor.element.$;
     //   console.log('mau')
     //   console.log(originalTextarea.name);
   // });
     
