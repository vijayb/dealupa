<?php


function dealupaScore(&$deal) {
  $company_score = 0;
  // Give a boost to high quality sites that don't have num_purchased
  // and so will never qualify for trending
  if ($deal["company_id"] == 7 || $deal["company_id"] == 10 || $deal["company_id"] == 38 || 
      $deal["company_id"] == 37) {
    $company_score = 2.5;
  } else if ($deal["company_id"] ==33) {
    $company_score = 1;
  }

  $trending_score = 0;
  if (isset($deal["trending"])) {
    if ($deal["trending"] == 1) {
      $trending_score = 3;
    } else if ($deal["trending"] == 2) {
      $trending_score = 5;
    } else if ($deal["trending"] == 3) {
      $trending_score = 10;
    }
  }

  $facebook_score = 1;
  if (isset($deal["fb_likes"])) {
    $facebook_score += $deal["fb_likes"];
  }
  if (isset($deal["fb_shares"])) {
    $facebook_score += $deal["fb_shares"];
  }

  if ($facebook_score >= 1) {
    if ($deal["company_id"] == 1) {
      // Groupon:
      $log_base = 6;
    } else if ($deal["company_id"] == 2 || $deal["company_id"] == 20) {
      // LivingSocial or Voice Daily Deals
      $log_base = 4;
    } else {
      // Everyone else
      $log_base = 3;
    }

    $facebook_score = log($facebook_score, $log_base);
  } else {
    $facebook_score = 0;
  }

  $yelp_score = 0;
  if (isset($deal["yelp_rating"]) && isset($deal["yelp_review_count"]) && $deal["yelp_review_count"] >= 1) {
    $multiplier = 1;
    if ($deal["yelp_rating"] >= 4.5) {
      $log_base = 3;
    } else if ($deal["yelp_rating"] >= 4) {
      $log_base = 4;
    } else if ($deal["yelp_rating"] >= 3) {
      $log_base = 5;
    } else if ($deal["yelp_rating"] >= 2) {
      $multiplier = -1;
      $log_base = 5;
    } else {
      $multiplier = -1;
      $log_base = 3;
    }

    // We shouldn't credit yelp reviews too much for GoldStar, since the reviews
    // are for the venue, not the performer (GoldStar does only will call events)
    if ($deal["company_id"] == 33) {
      $log_base = 4.5;
    }

    $yelp_score = $multiplier * log($deal["yelp_review_count"], $log_base);
  }

  $recency_score = 0;
  if (isset($deal["discovered"])) {
    $now = time();
    if ($now - $deal["discovered"] > 86400 * 20) {
      $recency_score = -50;
    } else if ($now - $deal["discovered"] > 86400 * 10) {
      $recency_score = -20;
    } else if ($now - $deal["discovered"] > 86400 * 7) {
      $recency_score = -7;
    } else if ($now - $deal["discovered"] > 86400 * 5) {
      $recency_score = -2;
    } else if ($now - $deal["discovered"] > 86400 * 3) {
      $recency_score = -1;
    }
  }


  return 50 + $yelp_score + $recency_score + $company_score + $trending_score + $facebook_score;
}



?>