package net.affscash.android.utils

import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.drawable.BitmapDrawable
import android.graphics.drawable.Drawable
import android.text.Html
import android.widget.TextView
import coil.ImageLoader
import coil.request.ImageRequest
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import net.affscash.android.Config

class CoilImageGetter(
    private val textView: TextView,
    private val imageLoader: ImageLoader
) : Html.ImageGetter {

    private val scope = CoroutineScope(Dispatchers.Main)

    override fun getDrawable(source: String?): Drawable {
        if (source == null) return EmptyDrawable()

        // Handle relative URLs returned by HTML content
        val finalUrl = if (!source.startsWith("http")) {
            val cleanPath = source.removePrefix("/")
            Config.BASE_URL + cleanPath.replace(" ", "%20")
        } else {
            source.replace(" ", "%20")
        }

        val placeholder = BitmapDrawablePlaceHolder()

        scope.launch {
            val request = ImageRequest.Builder(textView.context)
                .data(finalUrl)
                .build()

            val result = withContext(Dispatchers.IO) {
                imageLoader.execute(request)
            }

            val drawable = result.drawable
            if (drawable != null) {
                // Resize image to fit TextView width if necessary
                val width = textView.width - textView.paddingLeft - textView.paddingRight
                val aspectRatio = drawable.intrinsicWidth.toFloat() / drawable.intrinsicHeight.toFloat()
                
                val finalWidth = if (width > 0 && drawable.intrinsicWidth > width) width else drawable.intrinsicWidth
                val finalHeight = if (aspectRatio > 0) (finalWidth / aspectRatio).toInt() else drawable.intrinsicHeight

                drawable.setBounds(0, 0, finalWidth, finalHeight)
                placeholder.setDrawable(drawable)

                // Force TextView to re-render
                textView.text = textView.text
                textView.invalidate()
            }
        }

        return placeholder
    }

    private class BitmapDrawablePlaceHolder : Drawable() {
        private var drawable: Drawable? = null

        fun setDrawable(drawable: Drawable) {
            this.drawable = drawable
            setBounds(0, 0, drawable.bounds.width(), drawable.bounds.height())
        }

        override fun draw(canvas: Canvas) {
            drawable?.draw(canvas)
        }

        override fun setAlpha(alpha: Int) {
            drawable?.alpha = alpha
        }

        override fun setColorFilter(colorFilter: android.graphics.ColorFilter?) {
            drawable?.colorFilter = colorFilter
        }

        @Deprecated("Deprecated in Java", ReplaceWith("PixelFormat.TRANSLUCENT", "android.graphics.PixelFormat"))
        override fun getOpacity(): Int {
            return drawable?.opacity ?: android.graphics.PixelFormat.TRANSLUCENT
        }
    }

    private class EmptyDrawable : Drawable() {
        init { setBounds(0, 0, 0, 0) }
        override fun draw(canvas: Canvas) {}
        override fun setAlpha(alpha: Int) {}
        override fun setColorFilter(colorFilter: android.graphics.ColorFilter?) {}
        @Deprecated("Deprecated in Java", ReplaceWith("PixelFormat.TRANSPARENT", "android.graphics.PixelFormat"))
        override fun getOpacity(): Int = android.graphics.PixelFormat.TRANSPARENT
    }
}
