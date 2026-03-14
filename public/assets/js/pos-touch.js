// POS TOUCH SCRIPT

(function(){

window.posTouch = {

init:function(){

this.bindFullscreen();
this.bindFocusMode();

},

bindFullscreen:function(){

document.querySelectorAll('[data-pos-fullscreen]').forEach(btn=>{

btn.addEventListener('click',function(){

if(!document.fullscreenElement){
document.documentElement.requestFullscreen();
}else{
document.exitFullscreen();
}

});

});

},

bindFocusMode:function(){

document.querySelectorAll('[data-pos-focus]').forEach(btn=>{

btn.addEventListener('click',function(){

document.body.classList.toggle('pos-focus-mode');

});

});

}

};

document.addEventListener('DOMContentLoaded',function(){

if(window.posTouch){
window.posTouch.init();
}

});

})();