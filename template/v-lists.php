<?php  
/*
* List of All sessions
*/
$allSessons = $this->allSessions();
?>

<div id="listsSessions">
    <div class="sessionInner">
        <h2><?php _e('Sessions', 'v-sessions'); ?></h2>
        <div class="gridlists">
            <?php foreach($allSessons as $session):
                $session_id = get_post_meta( $session->ID, 'tokbox_session_id', true );
                $token = get_post_meta( $session->ID, 'tokbox_token', true );
                ?>
                <div 
                    data-sessionid="<?php echo $session_id; ?>" 
                    data-token="<?php echo $token; ?>" 
                    class="single-session">
                    <div class="thumbnail">
                        <?php if(has_post_thumbnail( $session->ID )): ?>
                            <?php echo get_the_post_thumbnail( $session, 'full', array('class' => 'sessionImg') ); ?>
                        <?php endif; ?>
                    </div>
                    <div class="content-aria">
                            <h3 class="session-title"><?php echo $session->post_title; ?></h3>
                            <button class="vjoinButton" type="submit"><?php _e('Join', 'wp_tokbox'); ?></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="videos">
        <div id="subscriber"></div>
        <div id="publisher"></div>
</div>