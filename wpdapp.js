function fetchHiveData(username) {
    hive.api.getDiscussionsByBlog({tag: username, limit: 10}, function(err, result) {
        if(!err) {
            result.forEach(post => {
                document.getElementById("content").innerHTML += `<h2>${post.title}</h2><p>${post.body}</p>`;
            });
        }
    });
}

function publishToHive(title, body) {
    hive_keychain.requestPost("username", title, body, "tags", "posting", function(response) {
        if(response.success) {
            console.log("Post successful");
        } else {
            console.log("Post failed");
        }
    });
}
