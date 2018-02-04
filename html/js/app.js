
jQuery( document ).ready(function( $ ) {
    
});

jQuery.fn.extend({

    check: function() {
      return this.each(function() {
        this.checked = true;
      });
    },
    
    uncheck: function() {
      return this.each(function() {
        this.checked = false;
      });
    },
    
    updateContent: function() {
      $(this).getContent($(this).data("url"));
    },
    
    getContent: function(url) {
      $(this).data("url", url);
      var element = $(this);
      $.ajax({
        url: url,
        type: 'GET',       
        success: function(data) {
	        //called when successful
          console.log(element);
          console.log(JSON.stringify(data.result.html));
	        element.html(data.result.html);
        },
        error: function(e) {
	        //called when there is an error
	        console.log("$().getContent Error: " + JSON.stringify(e));
        }
      });
    },

    getData: function(url, func) {
      $(this).data("url", url);
      var element = $(this);
      $.ajax({
        url: url,
        type: 'GET',       
        success: function(data) {
	        //called when successful
          console.log(element);
          console.log(JSON.stringify(data.result));
	        element.data("result", data.result);
          if (func)
            func(data.result);
        },
        error: function(e) {
	        //called when there is an error
	        console.log("$().getData Error: " + JSON.stringify(e));
          data = { error : 1 };
          element.data("result", data);
          if (func)
            func(data);
        }
      });
    },
    
    autoRefreshContent: function(url, milliseconds) {
      var element = $(this);
      element.getContent(url);
      return window.setInterval(function() {
        element.getContent(url)
      }, milliseconds);      
    },
    
    autoRefreshData: function(url, milliseconds, func) {
      var element = $(this);
      element.getData(url, func);      
      return window.setInterval(function() {
        element.getData(url, func);        
      }, milliseconds);      
    },
    
    bindContent: function(socket, context) {      
      var element = $(this);
      // console.log('bindContent received!');
      socket.on(context, function(msg){
        // console.log('bindContent message: ' + msg);
        element.html(msg.html);
      });  
    },
    
    bindData: function(socket, context, cbfunc) {
      // console.log('bindData received!');
      socket.on(context, function(msg){
        // console.log('bindData message: ' + msg);
        cbfunc(msg);
      });  
    }    
});

function putData(url, data) {
      
      $.ajax({
        processData : false,
        url: url,
        type: 'PUT',       
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'JSON',
        success: function(data) {
	        //called when successful
	        console.log("putData Success: " + JSON.stringify(data));
        },
        error: function(e) {
	        //called when there is an error
	        console.log("putData Error: " + JSON.stringify(e));
        }
      });
    } // put comma here if you want to add more functions


function getData(url, func) 
{      
  $.ajax({
    url: url,
    type: 'GET',       
    success: function(data) {
      //called when successful      
      if (func)
        func(data.result);
    },
    error: function(e) {
      //called when there is an error
      console.log("getData Error:" + JSON.stringify(e));
      data = { error : 1 };
      if (func)
        func(data);
    }
  });  
};
    
    
function cancelAutoRefresh(handle)
{
  clearInterval(handle);
}

// URL : https://{hostname}:{port}/{namespace}
function openSocket(url) 
{  
  var socket = io.connect(url, { secure: true, transports: ['websocket'] });
          
  socket.on('connection', function(socket){
    console.log('connected to: ' + url);
  
    socket.on('disconnect', function(){
      console.log('disconnected from: ' + url);
    });
  });

  return socket;
}

function sendData(socket, context, data)
{
  socket.emit(context, data);
}

function pullData(socket, context, data, milliseconds) {
 
  var localSocket = socket;
  var localContext = context; 
  var localData = data;
  
  sendData(localSocket, localContext, localData);
  
  return window.setInterval(function() {
    sendData(localSocket, localContext, localData)
  }, milliseconds);
      
};
