<?php

include "config.php";

/*
 * For all functions $dbh is a database connection
 */

/*
 * @return handle to database connection
 */
function db_connect($host, $port, $db, $user, $pw) {
	$string = "host=contrib-postgres.club.cc.cmu.edu port=5432 dbname=contrib_shuoc user=shuoc password=1";
	$conn = pg_connect($string);
	if( !$conn ){
		echo "error in connecting to database";
	}
	return $conn;
}

/*
 * Close database connection
 */ 
function close_db_connection($dbh) {
	pg_close($dbh);
}

/*
 * Login if user and password match
 * Return associative array of the form:
 * array(
 *		'status' =>  (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */
function login($dbh, $user, $pw) {
	$get = "select name from user_record where name = '$user' AND password = '$pw'";
	$get_ret = pg_query($dbh,$get);
	$arr = array(
                "status" => 0,
                "userID" => -1,
        );
	if( !$get_ret ){
		return $arr;
	}
        $arr['status'] = 1;
        $arr['userID'] = $user;
	return $arr;
}

/*
 * Register user with given password 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */
function register($dbh, $user, $pw) {
	$str = "insert into user_record(name,password) values ('$user','$pw');";
	$result = pg_query($dbh,$str);
	$arr = array(
		"status" => 0,
		"userID" => -1,
	);
	if( !$result ){
		return $arr;
	}
	$arr['status'] = 1;
	$arr['userID'] = $user;	
	return $arr;
}

/*
 * Register user with given password 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function post_post($dbh, $title, $msg, $me) {
	$arr = array(
                "status" => 0,
        );
	$now = date("Y-m-d H:i:s");
	$str = "insert into post(name,post_time,title,body) values ('$me','$now','$title','$msg')";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
	if( !$result ){
		return $arr;
	}
	$arr['status'] = 1;
	return $arr;

}


/*
 * Get timeline of $count most recent posts that were written before timestamp $start
 * For a user $user, the timeline should include all posts.
 * Order by time of the post (going backward in time), and break ties by sorting by the username alphabetically
 * Return associative array of the form:
 * array(
 *		'status' => (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_timeline($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
	$arr = array(
		"status" => 0,
		"posts" => array(
		),
	);
	if( $start == PHP_INT_MAX){
		$unix = date("Y-m-d H:i:s");
	}
	else{
		 $unix =  date('Y-m-d H:i:s',$start);
	}
	$str = "select postid, post.name, title, body, post_time
		from user_record, post
		where post_time < '$unix'
			AND post.name = user_record.name
		order by post_time desc, name
		limit $count;";
	$result = pg_query($dbh, $str)  or die(pg_last_error($dbh));
//	$result = pg_query($dbh, $str);
	if ( !$result ){
		return $arr;
	}
	while ($row = pg_fetch_row($result)) {
		$unix = strtotime($row[4]);
		$arr['posts'][] = array(	"pID" => $row[0],
					"username" => $row[1],
					"title" => $row[2],
					"content" => $row[3],
					"time" => $unix,
				);
	}
	$arr['status'] = 1;
	return $arr;	 
}

/*
 * Get list of $count most recent posts that were written by user $user before timestamp $start
 * Order by time of the post (going backward in time)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_user_posts($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
	 $arr = array(
                "status" => 0,
                "posts" => array(
                ),
        );
        if( $start == PHP_INT_MAX){
                $start = date("Y-m-d H:i:s");
        }
        $str = "select postid, post.name, title, body, post_time
                from post
                where post_time < '$start'
                        AND name = '$user'
                order by post_time desc, name
                limit $count;";
	$result = pg_query($dbh, $str);
        if ( !$result ){
                return $arr;
        }
        while ($row = pg_fetch_row($result)) {
                $unix = strtotime($row[4]);
                $arr['posts'][] = array(        "pID" => $row[0],
                                        "username" => $row[1],
                                        "title" => $row[2],
                                        "content" => $row[3],
                                        "time" => $unix,
                                );
        }
        $arr['status'] = 1;
        return $arr;	
}

/*
 * Deletes a post given $user name and $pID.
 * $user must be the one who posted the post $pID.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 or 2 for failure)
 * )
 */
function delete_post($dbh, $user, $pID) {
	$arr = array(
                "status" => 0,
        );
        $str = "delete from post 
		where name = '$user' AND postid = $pID";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
        $arr['status'] = 1;
        return $arr;
}

/*
 * Records a "like" for a post given logged-in user $me and $pID.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 for failure)
 * )
 */
function like_post($dbh, $me, $pID) {
	echo "like like like like like like<br>";
	 $arr = array(
                "status" => 0,
        );
        $str = "insert into like_record(postid, name) values ('$pID','$me')";
        $result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
        $arr['status'] = 1;
        return $arr;
}

/*
 * Check if $me has already liked post $pID
 * Return true if user $me has liked post $pID or false otherwise
 */
function already_liked($dbh, $me, $pID) {
	$str = " select * from like_record
		 where name = '$me' AND postid = '$pID'";
	$result = pg_query($dbh, $str);
	if( !$result ){
		return false;
	}
	$row = pg_fetch_row($result);
	$count = $row[0];
	if( !$count ){
		return false;
	}	
	return true;
}

/*
 * Find the $count most recent posts that contain the string $key
 * Order by time of the post and break ties by the username (sorted alphabetically A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of Post objects) ]
 * )
 */
function search($dbh, $key, $count = 50) {
	$arr = array(
                "status" => 0,
                "posts" => array(),
        );
	$str = "select postid, name, title, body, post_time
                from post
                where body like '%$key%'
                order by post_time desc, name
                limit $count";
	$result = pg_query($dbh, $str);
	if( !$result ){
		return $arr;
	}
	while ($row = pg_fetch_row($result)) {
                $unix = strtotime($row[4]);
                $arr['posts'][] = array(        "pID" => $row[0],
                                        "username" => $row[1],
                                        "title" => $row[2],
                                        "content" => $row[3],
                                        "time" => $unix,
                                );
        }
        $arr['status'] = 1;
        return $arr;
}
/*
 * Find all users whose username includes the string $name
 * Sort the users alphabetically (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'users' => [ (Array of user IDs) ]
 * )
 */
function user_search($dbh, $name) {
	$arr = array(
                "status" => 0,
		"users" => array(),
        );
        $str = "select name
		from user_record
		where name like '%$name%'
		order by name asc";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
              	  echo "debug(user_search): error<br>";
		  return $arr;
        }
	 while ($row = pg_fetch_row($result)) {
                $arr['users'][] = trim($row[0]);
		/* I dont know why need a trim here */ 
	}
        $arr['status'] = 1;
        return $arr;
}


/*
 * Get the number of likes of post $pID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of likes)
 * )
 */
function get_num_likes($dbh, $pID) {
	$arr = array(
                "status" => 0,
                "count" => 0,
        );
	$str = "select count(*) from like_record where postid = $pID;";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
	if( !$result ){
		return $arr;
	}
	$row = pg_fetch_row($result);
	$arr['count'] = $row[0];
	$arr['status'] = 1;
	return $arr;	
}

/*
 * Get the number of posts of user $uID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of posts)
 * )
 */
function get_num_posts($dbh, $uID) {
	$arr = array(
                "status" => 0,
                "count" => 0,
        );
	$str = "select count(*) from post where name = '$uID'";
        $result = pg_query($dbh, $str) or die(pg_last_error($dbh));
	if( !$result ){
                return $arr;
        }
        $row = pg_fetch_row($result);
        $arr['count'] = $row[0];
        $arr['status'] = 1;
        return $arr;
}

/*
 * Get the number of likes user $uID made
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of likes)
 * )
 */
function get_num_likes_of_user($dbh, $uID) {
	$arr = array(
                "status" => 0,
                "count" => 0,
        );
        $str = "select count(*) from like_record where name = '$uID'";
        $result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
        $row = pg_fetch_row($result);
        $arr['count'] = $row[0];
        $arr['status'] = 1;
        return $arr;
}

/*
 * Get the list of $count users that have posted the most
 * Order by the number of posts (descending), and then by username (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'users' => [ (Array of user IDs) ]
 * )
 */
function get_most_active_users($dbh, $count = 10) {
	$arr = array(
		"status" => 0,
		"users" => array(
		),
	);
	$str = "select name, count(*) as k
		from post
		group by name
		order by k desc, name
		";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
        while ($row = pg_fetch_row($result)) {
                $arr['users'][] = trim($row[0]);
	 }	
	$arr['status'] = 1;
        return $arr;
}

/*
 * Get the list of $count posts posted after $from that have the most likes.
 * Order by the number of likes (descending)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_most_popular_posts($dbh, $count = 10, $from = 0) {
	$arr = array(
                "status" => 0,
                "users" => array(
                ),
        );
	$unix =  date('Y-m-d H:i:s',$from);
	$str = "select post.postid,post.name, title,body, post_time
			from post,like_record
			where post_time > '$unix' AND post.postid = like_record.postid
			group by post.postid            
			order by count(*) desc
			limit $count";
// here if same amount of like, what's next sort parameter

	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
        while ($row = pg_fetch_row($result)) {
        	$temp = strtotime($row[4]);
		$arr['posts'][] = array(        "pID" => $row[0],
                                        "username" => $row[1],
                                        "title" => $row[2],
                                        "content" => $row[3],
                                        "time" => $temp,
                                );

	}
        $arr['status'] = 1;
        return $arr;
}

/*
 * Recommend posts for user $user.
 * A post $p is a recommended post for $user if like minded users of $user also like the post,
 * where like minded users are users who like the posts $user likes.
 * Result should not include posts $user liked.
 * Rank the recommended posts by how many like minded users like the posts.
 * The set of like minded users should not include $user self.
 *
 * Return associative array of the form:
 * array(
 *    'status' =>   (1 for success and 0 for failure)
 *    'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_recommended_posts($dbh, $count = 10, $user) {
	$arr = array(
                "status" => 0,
                "users" => array(
                ),
        );
	$str = " drop view if exists ref_$user;
		drop view if exists liked_post_$user;
		 create view liked_post_$user(postid) as ( select distinct postid 
						   from like_record 
						   where name in ( select distinct name 
								   from like_record 
								   where postid in ( select postid 
										     from like_record 
										     where name = '$user')
								      AND name != '$user'
								 ) 
							AND postid not in ( select postid 
									    from like_record 
									    where name = '$user')
						);
		create view ref_$user(postid,count) as (
				select like_record.postid, count(*)
				from liked_post_$user, like_record
				where liked_post_$user.postid = like_record.postid
				group by like_record.postid
				order by count(*) desc
 		);	
		select post.postid, name,title,body,post_time
		from post, ref_$user
		where post.postid = ref_$user.postid 
		order by ref_$user.count desc
		limit $count;
	     ";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
	if( !$result ){
                return $arr;
        }
        while ($row = pg_fetch_row($result)) {
                $temp = strtotime($row[4]);
                $arr['posts'][] = array(        "pID" => $row[0],
                                        "username" => $row[1],
                                        "title" => $row[2],
                                        "content" => $row[3],
                                        "time" => $temp,
                                );

        }
        $arr['status'] = 1;
        return $arr;
}

/*
 * Delete all tables in the database and then recreate them (without any data)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function reset_database($dbh) {
	$arr = array(
		"status" => 0,
	);
	$str = "delete from like_record;
		delete from post;
		delete from user_record;";
	$result = pg_query($dbh, $str) or die(pg_last_error($dbh));
        if( !$result ){
                return $arr;
        }
	$arr['status'] = 1;
	return $arr;
}

?>
