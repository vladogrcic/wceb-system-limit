<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'ProperDateInterval' ) ) :
    class ProperDateInterval {
        private $start = null;
        private $end = null;

        public function __construct(DateTime $start, DateTime $end) {
            $this->start = $start;
            $this->end = $end;
        }
        /**
        * Does this time interval overlap the specified time interval.
        */
        public function overlaps(ProperDateInterval $other) {
            $start = $this->getStart()->getTimestamp();
            $end = $this->getEnd()->getTimestamp();

            $oStart = $other->getStart()->getTimestamp();
            $oEnd = $other->getEnd()->getTimestamp();

            return $start < $oEnd && $oStart < $end;
        }
        /**
         * Checks if there is overlap between this interval and another interval.
         * @return bool
         */
        public function overlapBool(ProperDateInterval $other) {
            if(!$this->overlaps($other)) {
                return false;
            }
            else{
                return true;
            }
        }
        /**
         * Gets the overlap between this interval and another interval.
         * @return array If overlap exists.
         * @return bool If no overlap exists.
         */
        public function overlap(ProperDateInterval $other) {
            if(!$this->overlaps($other)) {
                return false;
            }

            $start = $this->getStart()->getTimestamp();
            $end = $this->getEnd()->getTimestamp();

            $oStart = $other->getStart()->getTimestamp();
            $oEnd = $other->getEnd()->getTimestamp();

            $overlapStart = NULL;
            $overlapEnd = NULL;
            if($start === $oStart || $start > $oStart) {
                $overlapStart = $this->getStart();
            } else {
                $overlapStart = $other->getStart();
            }

            if($end === $oEnd || $end < $oEnd) {
                $overlapEnd = $this->getEnd();
            } else {
                $overlapEnd = $other->getEnd();
            }

            return new ProperDateInterval($overlapStart, $overlapEnd);
        }
        /**
         * Gets the duration of overlapping time.
         * @return long The duration of this interval in seconds.
         */
        public function getDuration() {
            return $this->getEnd()->getTimestamp() - $this->getStart()->getTimestamp();
        }

        public function getStart() {
            return $this->start;
        }

        public function getEnd() {
            return $this->end;
        }
    }
endif;