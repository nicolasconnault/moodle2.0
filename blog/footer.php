                </td>
            </tr>
        </table>
    <?php print_container_end(); ?>
    </td>
<?php
print '<!-- End page content -->'."\n";

// The right column
$right_content = $PAGE->blocks->get_content_for_region(BLOCK_POS_RIGHT);
if (!empty($right_content) || $editing) {
    echo '<td style="vertical-align: top;" id="right-column">';
    echo '<!-- Begin right side blocks -->'."\n";
    print_container_start();
    blocks_print_group($PAGE, BLOCK_POS_RIGHT);
    print_spacer(1, 120, true);
    print_container_end();
    echo '<!-- End right side blocks -->'."\n";
    echo '</td>';
}
?>

    </tr>
</table>

<?php

print_footer($course);
?>
