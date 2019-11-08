<?php
/**
 * Template part for displaying Facebook events.
 */

?>

<article id="fb-event-<?php echo $event['id']; ?>" class="fb-event">
	<?php echo isset( $event['cover'] ) ? '<figure class="event-picture"><img src="' . $event['cover']['source'] . '" /></figure>' : ''; ?>

	<div class="event-content">
        <div class="event-date">
	        <?php $start_time = new DateTime( explode( '+', $event['start_time'] )[0] ); ?>
            <span class="event-month">
                <?php echo date_i18n( 'M', $start_time->getTimestamp() ); ?>
            </span>
            <span class="event-day"><?php echo $start_time->format( 'j' ); ?></span>
        </div>

        <div class="event-info">
            <h1><a href="//www.facebook.com/<?php echo $event['id'] ?>"
                   target="_blank"><?php echo $event['name'] ?></a></h1>

            <p class="event-location">
                <span class="event-time"><?php
                    printf( __( '%s hrs', 'stage' ), date_i18n( 'D', $start_time->getTimestamp() ) . ', ' . $start_time->format( 'H:i' ) );
                ?></span>
                <span class="event-place"><?php
                    echo isset( $event['place'] ) ? $event['place']['name'] : '';
                ?></span>
            </p>

            <?php if ( $people = array_filter( array(
                'attending'  => $event['attending_count'],
                'interested' => $event['interested_count'],
            ) ) ) :
                arsort( $people );
                reset( $people );
                if ( ( $group = key( $people ) ) == 'attending' ) {
                    $singular = '%s person takes part';
                    $plural   = '%s people attends';
                } else {
                    $singular = '%s person is interested';
                    $plural   = '%s people are interested';
                } ?>
                <p class="event-people"><?php printf( _n( $singular, $plural, $people[ $group ], 'stage' ), $people[ $group ] ); ?></p>
            <?php endif; ?>
        </div>

		<?php if ( isset( $event['ticket_uri'] ) ): ?>
            <footer>
                <a href="<?php echo $event['ticket_uri'] ?>"
                   target="_blank"><?php _e( 'Tickets' ) ?></a>
            </footer>
		<?php endif; ?>
	</div>
	<!-- .event-content -->
</article><!-- #event-## -->
